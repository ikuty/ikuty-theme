/**
 * Technical blog features
 */
jQuery(document).ready(function($) {
    
    // Auto-generate table of contents
    function generateTableOfContents() {
        var headings = $('.entry-content').find('h1, h2, h3, h4, h5, h6');
        if (headings.length > 2) {
            var tocHtml = '<div class="table-of-contents"><h3>目次</h3><ul class="toc-list">';
            var currentLevel = 0;
            
            headings.each(function(index) {
                var heading = $(this);
                var level = parseInt(heading.prop('tagName').substring(1));
                var id = 'heading-' + index;
                heading.attr('id', id);
                
                var title = heading.text();
                
                if (level > currentLevel) {
                    for (var i = currentLevel; i < level; i++) {
                        if (i > 0) tocHtml += '<ul class="toc-sublist">';
                    }
                } else if (level < currentLevel) {
                    for (var i = level; i < currentLevel; i++) {
                        tocHtml += '</ul>';
                    }
                }
                
                tocHtml += '<li><a href="#' + id + '" class="toc-link">' + title + '</a></li>';
                currentLevel = level;
            });
            
            // Close remaining open lists
            for (var i = 1; i < currentLevel; i++) {
                tocHtml += '</ul>';
            }
            
            tocHtml += '</ul></div>';
            
            // Insert TOC after the first paragraph or at the beginning
            var firstParagraph = $('.entry-content p').first();
            if (firstParagraph.length) {
                firstParagraph.after(tocHtml);
            } else {
                $('.entry-content').prepend(tocHtml);
            }
        }
    }
    
    // Smooth scrolling for TOC links
    $(document).on('click', '.toc-link', function(e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Reading time estimation
    function calculateReadingTime() {
        var text = $('.entry-content').text();
        var wordCount = text.split(/\s+/).length;
        var readingTime = Math.ceil(wordCount / 200); // 200 words per minute average
        
        if (readingTime > 0) {
            var readingTimeHtml = '<div class="reading-time"><i class="reading-time-icon">📖</i> 読了時間: 約' + readingTime + '分</div>';
            $('.entry-header').append(readingTimeHtml);
        }
    }
    
    // Add copy button to code blocks
    function addCodeCopyButtons() {
        $('pre code').each(function() {
            var codeBlock = $(this);
            var copyButton = $('<button class="copy-code-btn" title="コードをコピー">📋</button>');
            
            copyButton.on('click', function() {
                var text = codeBlock.text();
                navigator.clipboard.writeText(text).then(function() {
                    copyButton.text('✅').attr('title', 'コピーしました');
                    setTimeout(function() {
                        copyButton.text('📋').attr('title', 'コードをコピー');
                    }, 2000);
                });
            });
            
            codeBlock.parent().css('position', 'relative').append(copyButton);
        });
    }
    
    // Enhance code blocks with language labels
    function addLanguageLabels() {
        $('pre code[class*="language-"]').each(function() {
            var codeBlock = $(this);
            var className = codeBlock.attr('class');
            var language = className.match(/language-(\w+)/);
            
            if (language && language[1]) {
                var languageLabel = $('<div class="code-language">' + language[1].toUpperCase() + '</div>');
                codeBlock.parent().prepend(languageLabel);
            }
        });
    }
    
    // Initialize features on single post pages
    if ($('body').hasClass('single')) {
        //generateTableOfContents();
        calculateReadingTime();
        
        // Wait for Prism to load, then add enhancements
        setTimeout(function() {
            addCodeCopyButtons();
            addLanguageLabels();
        }, 500);
    }
    
    // Highlight current section in TOC on scroll
    var tocLinks = $('.toc-link');
    var headings = $('[id^="heading-"]');
    
    if (tocLinks.length && headings.length) {
        $(window).on('scroll', function() {
            var scrollTop = $(window).scrollTop();
            var current = '';
            
            headings.each(function() {
                var heading = $(this);
                if (heading.offset().top - 150 <= scrollTop) {
                    current = heading.attr('id');
                }
            });
            
            tocLinks.removeClass('active');
            if (current) {
                $('.toc-link[href="#' + current + '"]').addClass('active');
            }
        });
    }
});