# WP Acceptance

WP Acceptance is a toolkit that empowers developers and CI pipelines to test codebases using version controlled acceptance tests and sharable, defined file and database snapshots.

[☞ Read the docs](https://wpacceptance.readthedocs.io/)

## Requirements

* PHP 7.1+
* mysqli PHP extension
* Docker

[WP Local Docker](https://github.com/10up/wp-local-docker) is highly recommended as the local development environment but not required.

*Note:* WP Acceptance should be run on your HOST machine and not within Docker.

## How It Works

There are many acceptance tests frameworks out there. They all have one major flaw - everyone executing the acceptance tests must be running the exact same code on the exact same database and environment to guarantee the same results. Ensuring a team of developers (and a CI pipeline) are all using the same database in the same environment has been nearly impossible until now. WP Acceptance is unique in that it allows you to run your acceptance tests against a codebase in a defined, distributable file and database [snapshot](https://github.com/10up/wpsnapshots).

## Install

[Installation instructions are on the docs site.](https://wpacceptance.readthedocs.io/en/latest/install/)

## Usage

[Learn how to use WP Acceptance on the docs site](https://wpacceptance.readthedocs.io/en/latest/)

<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
