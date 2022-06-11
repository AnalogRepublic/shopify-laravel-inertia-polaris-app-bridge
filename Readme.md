# Laravel Shopify Starter with Inertia/React/AppBridge

## Install Shopify API Library

### We are going to need this library to do most things related to Shopify

-   `sail composer require shopify/shopify-api`

-   As of now (2022/06/08) shopify php api has a limitation of psr/log 1.1. We are updating the composer.json file of this project to include a github repo that improves this limitation so that the package can be installed in laravel 9.

```
composer.json
+++
"repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:AnalogRepublic/shopify-php-api.git"
        }
    ],
+++
```

-   Install the dev package with the branch just created `composer require shopify/shopify-api:dev-chore/psr-log-3`

-   Start with the fallback route for login, and actual login routes

-   Create the config/shopify.php file with the environment settings

-   Update the AppServiceProvider.php to include the Shopify Boot Up sequence

-   TODO: Update DB Handler to use User Model
