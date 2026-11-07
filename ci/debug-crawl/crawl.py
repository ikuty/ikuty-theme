#!/usr/bin/env python3
"""Crawl the local WordPress with WP_DEBUG_LOG enabled and summarize debug.log.

Run from anywhere; see ci/debug-crawl/README.md. Needs only Python 3 and Docker.
The script never touches production: it talks to http://localhost:8000 and the local wpcli container.
"""
import argparse
import collections
import json
import os
import re
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
PROBE_DIR = os.path.join(ROOT, "ci", "debug-crawl")
LOG = os.path.join(ROOT, ".debug-logs", "debug.log")
COMPOSE = ["docker", "compose", "-f", "docker-compose.yml", "-f", "docker-compose.debug.yml"]

ADMIN_PAGES = [
    "index.php", "edit.php", "post-new.php", "edit.php?post_type=page", "upload.php", "edit-comments.php",
    "edit-tags.php?taxonomy=category", "edit-tags.php?taxonomy=post_tag", "themes.php", "widgets.php",
    "customize.php", "plugins.php", "users.php", "profile.php", "tools.php", "tools.php?page=ikuty-login-history",
    "options-general.php", "options-reading.php", "options-permalink.php", "site-health.php", "update-core.php",
]


def wp(*args, volume=True):
    """Run wp-cli in the wpcli container and return stdout."""
    cmd = COMPOSE + ["run", "--rm", "--no-deps", "--pull", "never", "-T", "-v", PROBE_DIR + ":/probe:ro", "wpcli", "wp"] + list(args)
    r = subprocess.run(cmd, cwd=ROOT, capture_output=True, text=True)
    if r.returncode != 0:
        sys.exit("wp-cli failed: %s\n%s" % (" ".join(args), r.stdout + r.stderr))
    return r.stdout


def probe(*args):
    out = wp("eval-file", "/probe/probe.php", *args)
    for line in out.splitlines():
        if line.startswith("JSON "):
            return json.loads(line[5:])
    sys.exit("probe %s returned no JSON:\n%s" % (args, out))


class Http:
    def __init__(self, base, cookie=""):
        self.base, self.cookie = base.rstrip("/"), cookie
        self.jar = {}

    def get(self, path_or_url, data=None):
        url = path_or_url if path_or_url.startswith("http") else self.base + "/" + path_or_url.lstrip("/")
        # 日本語を含むURLは、パスをパーセントエンコードする(macOSのxargs/curlの引数長・文字コード問題を避けるため Python で処理する)
        parts = urllib.parse.urlsplit(url)
        url = urllib.parse.urlunsplit((parts.scheme, parts.netloc, urllib.parse.quote(parts.path, safe="/%:@"), parts.query, ""))
        headers = {"User-Agent": "ikuty-debug-crawl/1.0"}
        cookie = "; ".join(([self.cookie] if self.cookie else []) + ["%s=%s" % kv for kv in self.jar.items()])
        if cookie:
            headers["Cookie"] = cookie
        body = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
        try:
            r = urllib.request.urlopen(urllib.request.Request(url, data=body, headers=headers), timeout=120)
            for c in r.headers.get_all("Set-Cookie") or []:
                k, _, v = c.split(";")[0].partition("=")
                self.jar[k] = v
            return r.status, r.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            return e.code, e.read().decode("utf-8", "replace")
        except Exception as e:  # noqa: BLE001 - connection errors are reported as status 0
            return 0, str(e)


def sitemap_urls(http, base):
    seen, urls, queue = set(), [], [base + "/sitemap.xml", base + "/wp-sitemap.xml"]
    while queue:
        sm = queue.pop(0)
        if sm in seen:
            continue
        seen.add(sm)
        st, body = http.get(sm)
        if st != 200:
            continue
        try:
            root = ET.fromstring(body)
        except ET.ParseError:
            continue
        for loc in root.iter("{http://www.sitemaps.org/schemas/sitemap/0.9}loc"):
            (queue if root.tag.endswith("sitemapindex") else urls).append(loc.text.strip())
    # サイトマップのURLのホストは本番のもの。ローカルのホストに置き換えて巡回する
    out = []
    for u in urls:
        p = urllib.parse.urlsplit(u)
        out.append(urllib.parse.urlunsplit(("http", urllib.parse.urlsplit(base).netloc, p.path, p.query, "")))
    return list(dict.fromkeys(out))


def classify(path):
    if "/themes/" in path and "ikuty-theme" in path:
        return "theme"
    if "/plugins/" in path:
        return "plugin"
    return "core"


EXCLUDE = re.compile(r"api\.wordpress\.org|translations_api|http_request_failed|cURL error|wordpress\.org/", re.I)
LINE = re.compile(r"^\[[^\]]*\]\s+PHP\s+(Deprecated|Warning|Notice|Fatal error|Parse error):\s+(.*?)\s+in\s+(\S+)\s+on line\s+(\d+)")


def summarize_log(include_selftest):
    counts = collections.OrderedDict((k, collections.Counter()) for k in ("theme", "plugin", "core"))
    excluded, selftest_seen = 0, False
    if os.path.exists(LOG):
        for line in open(LOG, encoding="utf-8", errors="replace"):
            if EXCLUDE.search(line):
                excluded += 1
                continue
            m = LINE.match(line)
            if not m:
                continue
            level, msg, path, ln = m.groups()
            if path.endswith("mu-plugins/crawl-selftest.php"):
                selftest_seen = True
                continue
            counts[classify(path)][(level, msg[:120], path.replace("/var/www/html/wp-content/", ""), ln)] += 1
    return counts, excluded, selftest_seen


def main():
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument("--base", default="http://localhost:8000")
    ap.add_argument("--limit", type=int, default=0, help="crawl only the first N sitemap URLs (0 = all)")
    ap.add_argument("--keep-log", action="store_true", help="do not truncate debug.log before the run")
    a = ap.parse_args()

    if not os.path.isdir(os.path.dirname(LOG)):
        sys.exit("Debug logging is not enabled. See ci/debug-crawl/README.md (docker-compose.debug.yml).")
    st, _ = Http(a.base).get("/")
    if st != 200:
        sys.exit("The local site did not return 200 (%s)." % st)

    print("== snapshot")
    before = probe("snapshot")
    if not a.keep_log and os.path.exists(LOG):
        open(LOG, "w").close()

    print("== self test (the web log must record a known deprecation)")
    probe("selftest-install")

    ids = probe("ids")
    cookie = probe("cookie")
    try:
        status = collections.Counter()
        failures = []

        def hit(http, path, label):
            code, body = http.get(path)
            status[(label, code // 100 * 100 if code else 0)] += 1
            if code == 0 or code >= 500:
                failures.append((label, path, code))
            return code, body

        print("== front (anonymous, with cookies)")
        anon = Http(a.base)
        hit(anon, "/?crawl_selftest=1", "front")
        urls = sitemap_urls(anon, a.base)
        if a.limit:
            urls = urls[: a.limit]
        extras = ["/", "/?s=test", "/?s=", "/no-such-page-404/", "/feed/", "/?p=%d" % (ids["posts"][0] if ids["posts"] else 1)]
        for u in extras + urls:
            hit(anon, u, "front")
        print("   %d sitemap URLs + %d extras" % (len(urls), len(extras)))

        print("== admin (administrator cookie from wp-cli, no password)")
        admin = Http(a.base, cookie["cookie"])
        pages = list(ADMIN_PAGES) + ["post.php?post=%d&action=edit" % i for i in ids["posts"][:5] + ids["pages"][:2]]
        pages += ["term.php?taxonomy=category&tag_ID=%d&post_type=post" % i for i in ids["categories"][:2]]
        pages += ["term.php?taxonomy=post_tag&tag_ID=%d&post_type=post" % i for i in ids["tags"][:2]]
        for p in pages:
            hit(admin, "wp-admin/" + p, "admin")
        print("   %d pages" % len(pages))

        print("== hooks, shortcodes and OGP image generation")
        print("  ", probe("hooks"))

        print("== AJAX (term order; the same values are posted back, so nothing changes)")
        for label, var, tax, action, field in (
            ("tag", "tagOrderAjax", "post_tag", "update_tag_order", "tag_orders"),
            ("category", "categoryOrderAjax", "category", "update_category_order", "category_orders"),
        ):
            _, html = admin.get("wp-admin/edit-tags.php?taxonomy=" + tax)
            m = re.search(var + r'\s*=\s*\{[^}]*"nonce":"([^"]+)"', html)
            if not m:
                failures.append(("ajax", label + ": nonce not found", 0))
                continue
            nonce = m.group(1)
            valid = {"action": action, "nonce": nonce}
            orders = ids["orders"][label]
            # 空の配列は、PHPのJSONでは [] になるため、辞書でなければ空として扱う
            valid.update({"%s[%s]" % (field, k): v for k, v in (orders.items() if isinstance(orders, dict) else [])})
            cases = [
                ("valid", valid),
                ("no nonce", {"action": action, field + "[1]": 1}),
                ("wrong nonce", {"action": action, "nonce": "0000000000", field + "[1]": 1}),
                ("no orders", {"action": action, "nonce": nonce}),
                ("orders not array", {"action": action, "nonce": nonce, field: "abc"}),
                ("garbage keys", {"action": action, "nonce": nonce, field + "[abc]": "1", field + "[-3]": "7", field + "[0]": "5"}),
                ("nested value", {"action": action, "nonce": nonce, field + "[4][]": "x"}),
            ]
            for name, data in cases:
                code, body = admin.get("wp-admin/admin-ajax.php", data)
                print("   %-9s %-18s -> %s %s" % (label, name, code, body.strip()[:60]))
    finally:
        probe("selftest-remove")
        probe("cleanup", str(cookie["user_id"]), cookie["token"])
        probe("restore")

    print("== restore check")
    after = probe("digest")
    changed = [k for k in before if before[k] != after[k]]
    print("   data unchanged" if not changed else "   CHANGED: %s" % ", ".join(changed))

    print("== HTTP status")
    for (label, klass), n in sorted(status.items()):
        print("   %-6s %sxx: %d" % (label, klass // 100, n))
    for label, path, code in failures:
        print("   FAILED(%s) %s -> %s" % (label, path, code))

    time.sleep(1)
    counts, excluded, selftest_seen = summarize_log(True)
    print("== debug.log")
    print("   self test line recorded: %s" % ("yes" if selftest_seen else "NO (logging may not be working)"))
    print("   excluded (connection errors to wordpress.org): %d" % excluded)
    for k, c in counts.items():
        print("   %-6s: %d messages, %d distinct" % (k, sum(c.values()), len(c)))
        for (level, msg, path, ln), n in c.most_common(10):
            print("      %3dx %s: %s (%s:%s)" % (n, level, msg, path, ln))

    bad = bool(changed) or bool(failures) or not selftest_seen or sum(counts["theme"].values()) > 0
    print("== RESULT: %s" % ("FAIL" if bad else "OK (no theme messages)"))
    sys.exit(1 if bad else 0)


if __name__ == "__main__":
    main()
