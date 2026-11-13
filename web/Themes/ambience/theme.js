'use strict';

var mrbsColorScheme = (function() {
  var storageKey = 'mrbs-color-scheme';
  var validSchemes = ['light', 'auto', 'dark'];
  var labels = {
    light: 'Light',
    auto: 'Automatic',
    dark: 'Dark'
  };
  var icons = {
    light: '<circle cx="12" cy="12" r="3.5"></circle>' +
           '<path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42' +
           'M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"></path>',
    auto: '<rect x="3" y="4" width="18" height="14" rx="2"></rect>' +
          '<path d="M8 22h8M12 18v4"></path>' +
          '<path class="scheme-icon-fill" d="M12 8a3 3 0 1 0 0 6V8z"></path>' +
          '<circle cx="12" cy="11" r="3"></circle>',
    dark: '<path d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8a8.5 8.5 0 1 0 11.5 11.5z"></path>'
  };
  var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
  var html = document.documentElement;
  var picker;
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
    if (!picker)
    {
      return;
    }

    var controls = picker.querySelectorAll('button');
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

  function createPicker()
  {
    var target = document.querySelector('header.banner:not(.simple) > nav.container > nav:last-child');
    if (!target)
    {
      return;
    }

    picker = document.createElement('div');
    picker.className = 'color-scheme-picker';
    picker.setAttribute('data-color-scheme-picker', '');
    picker.setAttribute('role', 'group');
    picker.setAttribute('aria-label', 'Colour scheme');

    for (var i = 0; i < validSchemes.length; i++)
    {
      var scheme = validSchemes[i];
      var button = document.createElement('button');
      button.type = 'button';
      button.setAttribute('data-color-scheme', scheme);
      button.setAttribute('aria-label', labels[scheme]);
      button.setAttribute('title', labels[scheme]);
      button.setAttribute('aria-pressed', 'false');
      button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
                         icons[scheme] + '</svg>';
      button.addEventListener('click', function() {
          set(this.getAttribute('data-color-scheme'));
        });
      picker.appendChild(button);
    }

    target.appendChild(picker);
    updateControls();
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

  apply(getStored());
  createPicker();

  return {
    get: function() {
      return currentScheme;
    },
    set: set
  };
})();
