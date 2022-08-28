# Website

[![CI status](https://github.com/reactphp/website/actions/workflows/ci.yml/badge.svg)](https://github.com/reactphp/website/actions)
![Last deployed](https://img.shields.io/github/last-commit/reactphp/reactphp.github.io?label=last%20deployed&logo=github)

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

## Deploy

Once built (see previous chapter), deployment is as simple as hosting the static
website contents of the `tmp/build` directory behind a webserver of your choice.

We use GitHub Pages to deploy this to the live site. This is done by pushing the
contents of the `tmp/build` directory to the repository hosted in
[reactphp/reactphp.github.io](https://github.com/reactphp/reactphp.github.io).

This deployment can be started by executing this:

```bash
$ bin/build --deploy
```

Note that this will publish any changes you've made to your local repository,
including any uncommitted ones. There should usually be no need to do this
manually, see next chapter for auto-deployment.

## Auto-Deployment

The website can be automatically deployed via the GitHub Pages feature.

Any time a commit is merged (such as when a PR is merged), GitHub actions will
automatically build and deploy the website. This is done by running the above
deployment script (see previous chapter).

> Repository setup:
> We're using a SSH deploy key for pushing to this target repository.
> Make sure the required `DEPLOY_KEY` secret is set in the repository settings on GitHub.
> See [action documentation](https://github.com/JamesIves/github-pages-deploy-action#using-an-ssh-deploy-key-)
> for more details.

## License

Released under the [MIT](LICENSE) license.

**Note:** The logo and the brand name are not MIT licensed.
Please check their [LICENSE](https://github.com/reactphp/branding/blob/master/LICENSE)
for usage guidelines.
