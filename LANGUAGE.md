# MRBS Language Support

## Selection of Language

MRBS will determine which language to use as follows:

- If the config variable `$disable_automatic_language_changing` is set to `true`,
then MRBS will use the language specified by `$default_language_tokens`.
- Or else, if `$override_locale` is set, MRBS will use the language specified by `$override_locale`.
- Otherwise, MRBS will use the language specified by the browser's language preference settings.

## Language Files

MRBS comes with a set of language files in the web/lang/ directory that contain the text strings
for a language.  Not all of these files are complete.  If a string does not exist for a particular
language tag, then MRBS will look for a translation in the language specified by `$default_language_tokens`.
If it still cannot find a translation, then MRBS will use the English language file.

## Overriding the Translations

If you want to change the text string for a particular tag to suit your site's requirements, do not
modify the lang.* files. Instead, use the `$vocab_override` config setting.  By doing this you wiil
ensure that your changes will not be overwritten when you upgrade MRBS.  For example, if you
are using MRBS to book cars rather than rooms, you will need to change the text strings for the
'room' tag by setting:

~~~php
$vocab_override["en"]["room"] = "Car";
~~~

or, for French:

~~~php
$vocab_override["fr"]["room"] = "Voiture";
~~~

## Adding New Languages
Porting MRBS to another language is relatively easy. If no "lang.xx"
file is available for your language, copy "lang.en" to "lang.xx", where 'xx' is
the appropriate language tag for your language.  The language tags should be in
BCP 47 format and all lower-case, for example 'fr-ca' for French as spoken in Canada.
Then edit the file to change the English strings to your language.  For example, you might
change:

~~~php
$vocab["room"] = "Room";
~~~

to:

~~~php
$vocab["room"] = "Salle";
~~~

You will then find that MRBS starts to use your language file
automatically. If you are having trouble getting this to work, see the
language section of systemdefaults.inc.php.

This will then be the standard language file for that language.  If you want to override a translation, you can create a new language file with the same name as the standard language file, but with a different language tag.  For example, if you want to override the French language file, you can create a new file called `lang.fr-ca` and put your translation to suit
your site, then use the `$vocab_override` config variable.  For example,

~~~php
$vocab_override["fr"]["room"] = "Voiture";
$vocab_override["fr-ca"]["room"] = "Auto";
~~~

Once you have a new lang file, please submit a Pull Request to the MRBS GitHub repository
so that it can be included in future releases.

## Improving the Standard MRBS Translations
If you would like to complete or improve an existing language file, the
`checklang.php` script included in the distribution can help. Copy this
to your web server area.
Now use your browser to access:
         http://your-host-and-dir/checklang.php?lang=xx
(where "xx" is the code of the language file you want to check). This
will report missing or untranslated strings in "lang.xx" compared to
"lang.en". If you omit the parameter and access:
         http://your-host-and-dir/checklang.php
you will see the results of checking all available language files.

If you do create or improve a language file, please submit a Pull Request. Thanks!

## Language File Formats

The files are UTF-8 encoded.

Some of the language strings include conversion specifications for `sprintf()`, for example `%s` or `%d`.  Be
careful when the conversion specifications also include an argument index, for example `%1$s`,
to ensure that the language string is enclosed in single, rather than double quotes; otherwise, PHP will
try and interpret the `$` as the start of a variable name, and generate an error because
the variable name is not defined.
