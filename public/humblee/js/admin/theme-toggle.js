/**
 * Theme Switcher — supports light, dark, and named custom themes
 */

$(document).ready(function() {
    updateThemeIndicator();

    $(document).on('click', '.theme-option', function(e) {
        e.preventDefault();
        var newTheme = $(this).data('theme');
        if (newTheme && newTheme !== window.CURRENT_THEME) {
            applyTheme(newTheme);
        }
    });
});

function applyTheme(theme) {
    var themeLink = $('link[href*="theme-"]');
    if (themeLink.length) {
        var newHref = themeLink.attr('href').replace(/theme-[^/]+\.css/, 'theme-' + theme + '.css');
        themeLink.attr('href', newHref);
    }
    window.CURRENT_THEME = theme;
    updateThemeIndicator();
    saveThemePreference(theme);
}

function updateThemeIndicator() {
    var theme = window.CURRENT_THEME || 'light';
    $('.theme-option').removeClass('is-active');
    $('.theme-option[data-theme="' + theme + '"]').addClass('is-active');
}

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
