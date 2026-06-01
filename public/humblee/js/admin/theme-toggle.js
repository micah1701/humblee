/**
 * Theme Toggle - Light/Dark Mode Support
 * Handles switching between light and dark admin themes
 */

$(document).ready(function() {
    // Set initial label based on current theme
    updateThemeLabel();

    // Click handler for theme toggle
    $('#themeToggle').on('click', function(e) {
        e.preventDefault();
        toggleTheme();
    });
});

/**
 * Toggle between light and dark themes
 */
function toggleTheme() {
    var currentTheme = window.CURRENT_THEME || 'light';
    var newTheme = currentTheme === 'light' ? 'dark' : 'light';

    // Update the CSS file
    var themeLink = $('link[href*="theme-"]');
    if (themeLink.length) {
        var oldHref = themeLink.attr('href');
        var newHref = oldHref.replace('theme-' + currentTheme, 'theme-' + newTheme);
        themeLink.attr('href', newHref);
    }

    // Update global variable
    window.CURRENT_THEME = newTheme;

    // Update label
    updateThemeLabel();

    // Save preference to database via AJAX
    saveThemePreference(newTheme);
}

/**
 * Update the theme toggle label and icon
 */
function updateThemeLabel() {
    var theme = window.CURRENT_THEME || 'light';
    var label = $('#themeLabel');
    var icon = $('#themeToggle .icon i');

    if (theme === 'dark') {
        label.text('Switch to Light Mode');
        icon.removeClass('fa-moon').addClass('fa-sun');
    } else {
        label.text('Switch to Dark Mode');
        icon.removeClass('fa-sun').addClass('fa-moon');
    }
}

/**
 * Save theme preference to database
 */
function saveThemePreference(theme) {
    $.post(XHR_PATH + 'setThemePreference', {
        theme: theme,
        hmac_key: window.hmac_key || '',
        hmac_token: window.hmac_token || ''
    }, function(response) {
        if (!response.success && response.error) {
            console.error('Failed to save theme preference:', response.error);
            quickNotice('Error saving theme preference', 'error');
        }
    }, 'json').fail(function() {
        console.error('AJAX request failed');
        quickNotice('Error saving theme preference', 'error');
    });
}

