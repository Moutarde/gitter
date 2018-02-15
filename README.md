# Gitter 
[![Build Status](https://secure.travis-ci.org/patrikx3/gitter.png)](http://travis-ci.org/patrikx3/gitter)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/patrikx3/gitter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/patrikx3/resume-web/?branch=master)
![Code Coverage](https://scrutinizer-ci.com/g/patrikx3/gitter/badges/coverage.png?b=master)


This is Klaus Silveira's fork, with sub-modules.

### PHP 7 only

Gitter allows you to interact in an object oriented manner with Git repositories
via PHP. The main goal of the library is not to replace the system `git` command,
but provide a coherent, stable and performatic object oriented interface.

Most commands are sent to the system's `git` command, parsed and then interpreted
by Gitter. Everything is transparent to you, so you don't have to worry about a thing. 

## Releases

### v1.0.0
* The minimum PHP version is 7.1

### v0.3.5
* Fixed PHPUNIT

#### v0.3.4
* Different submodule links for Gitlist and Github

#### v0.3.2, v0.3.3
* Added submodules

#### v0.3.1
* Moved to patrikx3 repo

## Requirements

* git (http://git-scm.com) (tested with 1.7.5.4)

## Usage

Gitter is very easy to use and you'll just need a few method calls to get 
started. For example, to create a new repository:

    $client = new Gitter\Client;
    $repository = $client->createRepository('/home/user/test');

Or a bare repository:

    $client = new Gitter\Client;
    $repository = $client->createRepository('/home/user/test', true);

Or to open an existing repository: 

    $client = new Gitter\Client;
    $repository = $client->getRepository('/home/user/anothertest');

Both methods will return a `Repository` object, which has various methods
that allow you to interact with that repository.

### Getting a list of commits

Once you get hold of the `Repository` object, you can use: 

    $commits = $repository->getCommits();
    print_r($commits);

To get a list of various commits.

### Getting a single commit

Given a specific commit hash, you can find information about that commit:

    $commit = $repository->getCommit('920be98a05');
    print_r($commit);
    
### Getting statistics for repository

Statistics aggregators can be added to the repository:

    $repository->addStatistics(array(
        new Gitter\Statistics\Contributors,
        new Gitter\Statistics\Date,
        new Gitter\Statistics\Day,
        new Gitter\Statistics\Hour
    ));
    print_r($repository->getStatistics());