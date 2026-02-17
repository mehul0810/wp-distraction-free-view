<h1><p align="center">WP Distraction Free View 💚</p></h1>

<p align="center">This WordPress plugin will help you achieve a distraction free view for your pages, posts and custom post types.</p>

---

👉🏻 Not a developer? Running WordPress? [Download WP Distraction Free View](https://wordpress.org/plugins/wp-distraction-free-view/) on WordPress.org.

![WordPress version](https://img.shields.io/wordpress/plugin/v/wp-distraction-free-view.svg) ![WordPress Rating](https://img.shields.io/wordpress/plugin/r/wp-distraction-free-view.svg) ![WordPress Downloads](https://img.shields.io/wordpress/plugin/dt/wp-distraction-free-view.svg) [![License](https://img.shields.io/badge/license-GPL--3.0%2B-green.svg)](https://github.com/mehul0810/wp-distraction-free-view/blob/master/license.txt) 

Welcome to the GitHub repository for "WP Distraction Free View" WordPress Plugin. This is the core repository and heart of an ecosystem of active development. Here you can browse the source, look at open issues, and contribute to the project. 

Happy Coding!
 
 ## 🙋 Support
 
 This repository is not suitable for support. Please don't use GitHub issues for non-development related support requests. Don't get us wrong, we're more than happy to help you! However, to get the support you need please use the following channels:

* [WP.org Support Forums](https://wordpress.org/support/plugin/wp-distraction-free-view) - for all **free** users.
* [Documentation](https://mehulgohil.com/contact) - for all **admins** (coming soon). 
 
## 🌱 Getting Started 

If you're looking to contribute or actively develop on the plugin, Welcome! We're glad you're here. Please ⭐️ this repository and fork it to begin local development. 

Most of us are using [Local by Flywheel](https://localbyflywheel.com/) to develop on WordPress, which makes set up quick and easy. If you prefer [Docker](https://www.docker.com/), [VVV](https://github.com/Varying-Vagrant-Vagrants/VVV), or another flavor of local development that's cool too!

## ✅ Prerequisites
* [Node.js](https://nodejs.org/en/) as JavaScript engine
* [NPM](https://docs.npmjs.com/) npm command globally available in CLI

## 💻 Local Development 

To get started developing on the WP Distraction Free View, you will need to perform the following steps:

1. Create a new WordPress site with any URL
2. `cd` into your local plugins directory: `/path/to/wp-content/plugins/`
3. Fork this repository from GitHub and then clone that into your plugins directory in a new `wp-distraction-free-view` directory
4. Run `composer install` to install PHP dependencies
5. Run `npm install` to get the necessary npm packages
6. Activate the plugin in WordPress
7. Run `npm run start` to start the development watch mode which will build the JS and SCSS files with live reload

That's it. You're now ready to start development.

**Alternative: Using wp-env**

You can also use `@wordpress/env` for a local development environment:
1. Run `npm -g i @wordpress/env` to install wp-env globally
2. Run `wp-env start` in the plugin directory to start a local WordPress instance
3. The plugin will be automatically activated
4. Access your site at `http://localhost:8888` (admin: `http://localhost:8888/wp-admin`)
5. Login with username `admin` and password `password`

**Available commands**

| Command             | Description  |
| :------------- | :------------ |
| `npm run start`      | Start development watch mode with live reload for JS and SASS files. Includes source maps for debugging. |
| `npm run build`      | Build optimized production files with minification. This is what gets deployed. |
| `npm run format`     | Format code using Prettier according to WordPress standards. |
| `npm run lint:js`    | Check JavaScript files for code quality issues using ESLint. |
| `npm run lint:php`   | Check PHP files against WordPress Coding Standards using PHPCS. |
| `npm run lint:php:fix` | Automatically fix PHP coding standard violations using PHPCBF. |
| `npm run plugin-zip` | Create a distributable plugin ZIP file for WordPress.org. |

**Development Notes**

* Ensure that you have `SCRIPT_DEBUG` enabled within your wp-config.php file. Here's a good example of wp-config.php for debugging:
    ```
     // Enable WP_DEBUG mode
    define( 'WP_DEBUG', true );
    
    // Enable Debug logging to the /wp-content/debug.log file
    define( 'WP_DEBUG_LOG', true );
   
    // Loads unminified core files
    define( 'SCRIPT_DEBUG', true );
    ```
* Commit the `package.lock` file. Read more about why [here](https://docs.npmjs.com/files/package-lock.json). 
* Your editor should recognize the `.eslintrc` and `.editorconfig` files within the Repo's root directory. Please only submit PRs following those coding style rulesets. 
