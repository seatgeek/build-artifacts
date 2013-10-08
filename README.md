# build-artifacts

Keep track of uploaded build artifacts in the cloud.

## Description

Running unit tests is great, but in certain contexts it may be useful to see specific build artifacts. This can be for any number of reasons:

- difficulty of setting up local development requirements
- build logs may have important debug info
- builds may have created debug binaries

In our use case, we run the unit tests for the [SeatGeek Android App](http://seatgeek.com/android-app) on [TravisCI](http://travis-ci.com/). Travis notifies our hipchat account and sends an email to the primary dev in charge regarding the build status. We also occasionally send out test builds to internal android users and external testers.

This is possible for all devs on the team - we have lots of docs on setting up our android environment - and even for non-devs, though giving access to non-technical people was a haphazard process. Rather than burden a developer with the task of disseminating test APKs to our internal Android users - and remote testers - we built an admin to allow them to retrieve the APKs on their own.

## How it works

TravisCI has the ability to [push build artifacts to S3](http://about.travis-ci.org/blog/2012-12-18-travis-artifacts/) using the [travis-artifacts](https://rubygems.org/gems/travis-artifacts) gem. It also has the ability to add webhooks to the running of individual builds.

The basic process is:

- Push a build to travis
- On build completion, travis sends webhook payload to the `build-artifacts` tool
- The build-artifacts tool parses and inserts each payload into an elasticsearch index
- User browses to the web panel to view builds with their related artifacts

## Features

- TravisCI webhooks integration
- Github branch filtering
- Custom branding

## Requirements

- Heroku
- An associated github repository
- A TravisCI Account
- If installing locally:
  - PHP 5.5
  - ElasticSearch 0.90.x
  - Redis 2.6.x

## Heroku Setup

First, you'll need to create a new heroku app:

    # this outputs a git remote we'll use later
    heroku apps:create

    # we'll assume the output is similar to:
    # http://ancient-foot-stomps-alligator.herokuapp.com/ | git@heroku.com:ancient-foot-stomps-alligator.git


And clone our repository:

    git clone git://github.com/seatgeek/build-artifacts.git
    cd build-artifacts
    git remote add heroku git@heroku.com:ancient-foot-stomps-alligator.git

Now we need to configure the buildpack. It has been tested against CHH's excellent [`heroku-buildpack-php` buildpack](https://github.com/CHH/heroku-buildpack-php).

    heroku config:set BUILDPACK_URL=git://github.com/CHH/heroku-buildpack-php

Add the excellent [Redis To Go](http://redistogo.com/) and [Searchbox](http://www.searchbox.com/) heroku plugins:

    heroku addons:add redistogo
    heroku addons:add searchbox

Next you'll want to set the proper config for Github and Travis:

    gem install travis                            # install the travis gen
    travis login                                  # login
    TRAVIS_TOKEN=$(travis token|cut -d' ' -f-1)   # export token

    # hash the repo name with your travis-ci GH token
    export GITHUB_USER='username'
    export GITHUB_REPO='repository'
    export REPO="${GITHUB_USER}/${GITHUB_REPO}"
    REPO_TOKEN=$(ruby -e "require 'digest'; puts Digest::SHA256.new.hexdigest(\"$REPO\" + \"$TRAVIS_TOKEN\")")

    # retrieve a custom GH token for this app
    curl -u $GITHUB_USER -d '{"note":"Travis Artifacts"}' https://api.github.com/authorizations

    heroku config:set TRAVIS_TOKEN=$REPO_TOKEN
    heroku config:set GITHUB_REPO=$GITHUB_REPO
    heroku config:set GITHUB_TOKEN=SOME_TOKEN_HERE
    heroku config:set GITHUB_USER=$GITHUB_USER

Now deploy your application!

    git push heroku master

Finally, you'll want to follow the [TravisCI post](http://about.travis-ci.org/blog/2012-12-18-travis-artifacts/) on uploading build artifacts to S3, and add the associated webhook to your repository's `.travis.yml` file:

    notifications:
      webhooks:
        urls:
          - http://ancient-foot-stomps-alligator.herokuapp.com/travisci/

Any subsequent pushes will create entries in your build-artifacts web panel.

## Options

The following are environment variables that can be set for the application:

- `BUILDPACK_URL`:         Buildpack to be used
- `CACHE_EXPIRATION`:      Redis cache expiration in seconds
- `DOWNLOAD_URL_TEMPLATE`: Template to use for displaying artifact download urls
- `ELASTICSEARCH_URL`:     Url to use to connect to elasticsearch over http. If not set, it can fallback to `SEARCHBOX_URL`
- `GITHUB_REPO`:           Github repository name associated to this index
- `GITHUB_TOKEN`:          Github token for this app. See **Setup*** for more instructions on generating this.
- `GITHUB_USER`:           Github user/organization associated with the repository
- `PAGE_TITLE`:            Title to show at top of page
- `REDIS_URL`:             Url to use to connect to redis. If not set, it can fallback to `REDISTOGO_URL`
- `SHOW_BRANCHES`:         Whether to show a list of branch filters at the top of the page. Valid values include `0` and `1`
- `TIMEZONE`:              [Timezone](http://php.net/manual/en/timezones.php) to show application in. Defaults to `UTC`
- `TRAVIS_IDENTIFIER`:     Identifier to use from travis payload for each build. Defaults to the build `number`.
- `TRAVIS_TOKEN`:          A generated travis-ci token. See **Setup*** for more instructions on generating this.

## Roadmap

- RSS feed
- Add screenshots
- Add support for artifacts by job
- Add support for multiple artifact templates
- Custom authentication support
- ACL to limit certain branches/artifacts to specific users

## License

The MIT License (MIT)

Copyright (c) 2013 SeatGeek

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.