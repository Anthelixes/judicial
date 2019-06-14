<!-- @file Instructions for subtheming using the Sass Starterkit. -->
<!-- @defgroup sub_theming_sass -->
<!-- @ingroup sub_theming -->
# Sass Starterkit

Below are instructions on how to create a Bootstrap sub-theme using a Sass
preprocessor.

- [Prerequisites](#prerequisites)
- [Additional Setup](#setup)
- [Overrides](#overrides)

## Compiling the CSS files

```
npm install
./node_modules/.bin/gulp
```

## Update Bootstrap

Download and extract the **latest** 3.x.x version of [Bootstrap Framework Source Files] into `bootstrap` folder.

**WARNING:** Do not modify files inside `bootstrap` to allow easy upgrades in the future.

## Overrides {#overrides}

The `scss/_default-variables.scss` - provide default variables used by the [Bootstrap Framework] instead of its own.

The `scss/overrides.scss` - contains various Drupal overrides to integrate with the [Bootstrap Framework].
It may contain a few enhancements, feel free to edit this file as you see fit.

The `scss/style.scss` file is the glue that combines `_default-variables.scss` and `overrides.scss` file together.
Edit only when need to add or remove files to be imported. It compiles to `./judicial_portal/css/style.css`

[Bootstrap Framework]: https://getbootstrap.com/docs/3.3/
[Bootstrap Framework Source Files]: https://github.com/twbs/bootstrap-sass
[Sass]: http://sass-lang.com
