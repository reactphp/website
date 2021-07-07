# Website

Source code of reactphp.org.

## Setup

1. Copy `.env.dist` to `.env` and add a
   [personal access token](https://github.com/settings/tokens) to the
   `GITHUB_TOKEN` entry.

   You don't need to check any of the scopes, `public access` is enough. The
   build script uses the GitHub API to render markdown files and fetch
   repository data and using the access token ensures that you don't run into
   API rate limits.

2. Install dependencies with `$ composer install`.

## Build

Once set up, you can build the website by executing this:

```bash
$ bin/build
```

This script will fetch all project repositories and then rebuild the entire website.
The resulting static website will be built into the `tmp/build` directory.

If you're working on the website source code, you may want to skip fetching all
components on every build like this:

```bash
$ bin/build --no-component-update
```

If you're working on the website CSS or Javascript code, you will have to
rebuild the static assets like this:

```bash
$ npm run-script build
```

> Note that compiled assets are expected to change much less frequently and are
  under version control. Run `npm install` to install and later commit any changes
  in `static-files/assets/`.

## Auto-Deployment with Travis CI

The website can be automatically deployed via the Travis CI
[GitHub Pages Deployment](https://docs.travis-ci.com/user/deployment/pages/)
feature.

Make sure, the required environment variables are set in the repository settings
on Travis CI: `GITHUB_TOKEN` 
([a personal access token](https://docs.travis-ci.com/user/deployment/pages/#Setting-the-GitHub-token)), 
`DEPLOY_REPO`, `DEPLOY_TARGET_BRANCH` and `DEPLOY_FQDN`.

## License

Released under the [MIT](LICENSE) license.

**Note:** The logo and the brand name are not MIT licensed.
Please check their [LICENSE](https://github.com/reactphp/branding/blob/master/LICENSE)
for usage guidelines.
