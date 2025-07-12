# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **WordPress Docker development environment** with a custom theme based on the Underscores (_s) starter theme. The project consists of a containerized WordPress installation with MySQL database and phpMyAdmin for local development.

## Architecture

**Docker Services:**
- `wordpress`: WordPress site (port 8000)
- `db`: MySQL 5.7 database  
- `phpmyadmin`: Database management interface (port 8081)

**Theme Structure (`original-theme/`):**
- Based on Automattic's Underscores (_s) starter theme
- Theme name: `original-theme` (text domain: `original-theme`)
- Function prefix: `original_theme_`
- Main theme files: `functions.php`, `style.css`, template files
- Modular structure with `/inc/` directory for includes
- Sass-based CSS compilation workflow

## Development Commands

**Docker Environment:**
```bash
docker-compose up -d    # Start all services
docker-compose down     # Stop all services
```

**Theme Development (run from `original-theme/` directory):**

**CSS/Sass:**
```bash
npm run watch          # Watch Sass files and auto-compile
npm run compile:css    # Compile Sass to CSS with linting
npm run compile:rtl    # Generate RTL stylesheet
```

**Code Quality:**
```bash
npm run lint:scss      # Lint Sass files
npm run lint:js        # Lint JavaScript files
composer lint:wpcs     # Check PHP against WordPress Coding Standards
composer lint:php      # Check PHP syntax errors
```

**Internationalization:**
```bash
composer make-pot      # Generate .pot translation file
```

**Distribution:**
```bash
npm run bundle         # Create distribution zip file
```

## Theme Architecture

**Function Naming Convention:**
- All functions prefixed with `original_theme_`
- Text domain: `original-theme`
- Version constant: `_S_VERSION`

**Key Theme Features:**
- Custom header support (`inc/custom-header.php`)
- Template tags (`inc/template-tags.php`) 
- Template functions (`inc/template-functions.php`)
- Customizer integration (`inc/customizer.php`)
- Jetpack compatibility (`inc/jetpack.php`)
- HTML5 markup support
- Custom logo and background support
- Navigation menu registration
- Widget area registration

**File Structure:**
- Template files: `index.php`, `single.php`, `page.php`, `archive.php`, etc.
- Template parts: `template-parts/content-*.php`
- JavaScript: `js/navigation.js`, `js/customizer.js`
- Sass source: `sass/` directory (compiled to `style.css`)

## Development Notes

- WordPress site accessible at `http://localhost:8000`
- phpMyAdmin at `http://localhost:8081`
- Theme files mounted directly to container for live editing
- Database credentials: `wordpress`/`wordpress` (user/password)
- Requires Node.js and Composer for theme development tools
- Theme supports RTL languages and includes RTL stylesheet generation