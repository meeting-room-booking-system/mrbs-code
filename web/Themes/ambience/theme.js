'use strict';

// Apply the preferred colour scheme before the body is rendered.  This avoids a
// flash of the light theme when a user has selected dark mode.
var mrbsColorScheme = (function() {
  var storageKey = 'mrbs-color-scheme';
  var validSchemes = ['light', 'auto', 'dark'];
  var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
  var html = document.getElementsByTagName('html')[0];
  var currentScheme;

  function isValid(scheme)
  {
    return validSchemes.indexOf(scheme) !== -1;
  }

  function getStored()
  {
    try
    {
      var stored = window.localStorage.getItem(storageKey);
      return isValid(stored) ? stored : 'auto';
    }
    catch (error)
    {
      return 'auto';
    }
  }

  function updateControls()
  {
    var controls = document.querySelectorAll('[data-color-scheme-picker] button');

    for (var i = 0; i < controls.length; i++)
    {
      var selected = controls[i].getAttribute('data-color-scheme') === currentScheme;
      controls[i].setAttribute('aria-pressed', selected ? 'true' : 'false');
    }
  }

  function apply(scheme)
  {
    currentScheme = isValid(scheme) ? scheme : 'auto';
    html.setAttribute('data-color-scheme', currentScheme);
    html.classList.toggle('dark', (currentScheme === 'dark') ||
                                  ((currentScheme === 'auto') && mediaQuery.matches));
    updateControls();
  }

  function set(scheme)
  {
    if (!isValid(scheme))
    {
      return;
    }

    try
    {
      window.localStorage.setItem(storageKey, scheme);
    }
    catch (error)
    {
      // Storage can be unavailable in private browsing modes.
    }

    apply(scheme);
  }

  function systemSchemeChanged()
  {
    if (currentScheme === 'auto')
    {
      apply('auto');
    }
  }

  if (typeof mediaQuery.addEventListener === 'function')
  {
    mediaQuery.addEventListener('change', systemSchemeChanged);
  }
  else if (typeof mediaQuery.addListener === 'function')
  {
    mediaQuery.addListener(systemSchemeChanged);
  }

  document.addEventListener('DOMContentLoaded', function() {
      var controls = document.querySelectorAll('[data-color-scheme-picker] button');

      for (var i = 0; i < controls.length; i++)
      {
        controls[i].addEventListener('click', function() {
            set(this.getAttribute('data-color-scheme'));
          });
      }

      updateControls();
    });

  apply(getStored());

  return {
    get: function() {
      return currentScheme;
    },
    set: set
  };
})();
