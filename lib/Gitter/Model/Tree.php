<?php

/*
 * This file is part of the Gitter library.
 *
 * (c) Klaus Silveira <klaussilveira@php.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gitter\Model;

use Gitter\Repository;
use function Stringy\create;
use Stringy\Stringy as S;

class Tree extends Item implements \RecursiveIterator
{
    protected $data;
    protected $position = 0;
    private $submodules = null;

    public function __construct($hash, Repository $repository)
    {
        $this->setHash($hash);
        $this->setRepository($repository);
    }

    private function getSubmodules($files, $hash) {
        if ($this->submodules === null) {
            foreach ($files as $file) {
                if ($file[4] === '.gitmodules') {
                    $branch = $hash;
                    $gitsubmodule = $this->getRepository()->getBlob("$branch:\"$file[4]\"")->output();
                    $this->submodules = parse_ini_string($gitsubmodule, true);
                }
            }
        }
        return $this->submodules;
    }

    public function parse()
    {
        $data = $this->getRepository()->getClient()->run($this->getRepository(), 'ls-tree -lz ' . $this->getHash());
        $lines = explode("\0", $data);
        $files = array();
        $root = array();

        foreach ($lines as $key => $line) {
            if (empty($line)) {
                unset($lines[$key]);
                continue;
            }
            $files[] = preg_split("/[\s]+/", $line, 5);
        }

        foreach ($files as $file) {
            // submodule
            if ($file[0] == '160000') {
                $submodules = $this->getSubmodules($files, $this->getHash());
                $shortHash = $this->getRepository()->getShortHash($file[2]);
                $tree = new Module;
                $tree->setMode($file[0]);
                $tree->setName($file[4]);
                $tree->setHash($file[2]);
                $tree->setShortHash($shortHash);
                $url = $submodules["submodule $file[4]"]['url'];
                if (preg_match('/^https?:\/\/(www\.)?github.com\//i', $url)) {
                    $s = S::create($url);
                    if ($s->endsWith('.git')) {
                        $url = substr($url, 0, strlen($url) - 4);
                    }
                }
                $tree->setUrl($url);
                $root[] = $tree;
                continue;
            }

            if ($file[0] == '120000') {
                $show = $this->getRepository()->getClient()->run($this->getRepository(), 'show ' . $file[2]);
                $tree = new Symlink;
                $tree->setMode($file[0]);
                $tree->setName($file[4]);
                $tree->setPath($show);
                $root[] = $tree;
                continue;
            }

            if ($file[1] == 'blob') {
                $blob = new Blob($file[2], $this->getRepository());
                $blob->setMode($file[0]);
                $blob->setName($file[4]);
                $blob->setSize($file[3]);
                $root[] = $blob;
                continue;
            }

            $tree = new Tree($file[2], $this->getRepository());
            $tree->setMode($file[0]);
            $tree->setName($file[4]);
            $root[] = $tree;
        }

        $this->data = $root;
    }

    public function output()
    {
        $files = $folders = array();

        foreach ($this as $node) {
            if ($node instanceof Blob) {
                $file['type'] = 'blob';
                $file['name'] = $node->getName();
                $file['size'] = $node->getSize();
                $file['mode'] = $node->getMode();
                $file['hash'] = $node->getHash();
                $files[] = $file;
                continue;
            }

            if ($node instanceof Tree) {
                $folder['type'] = 'folder';
                $folder['name'] = $node->getName();
                $folder['size'] = '';
                $folder['mode'] = $node->getMode();
                $folder['hash'] = $node->getHash();
                $folders[] = $folder;
                continue;
            }

            if ($node instanceof Module) {

                $folder['type'] = 'module';
                $folder['name'] = $node->getName();
                $folder['size'] = '';
                $folder['mode'] = $node->getMode();
                $folder['hash'] = $node->getHash();
                $folder['shortHash'] = $node->getShortHash();
                $folder['url'] = $node->getUrl();
                $folders[] = $folder;
                continue;
            }

            if ($node instanceof Symlink) {
                $folder['type'] = 'symlink';
                $folder['name'] = $node->getName();
                $folder['size'] = '';
                $folder['mode'] = $node->getMode();
                $folder['hash'] = '';
                $folder['path'] = $node->getPath();
                $folders[] = $folder;
            }
        }

        // Little hack to make folders appear before files
        $files = array_merge($folders, $files);

        return $files;
    }

    public function valid()
    {
        return isset($this->data[$this->position]);
    }

    public function hasChildren()
    {
        return is_array($this->data[$this->position]);
    }

    public function next()
    {
        $this->position++;
    }

    public function current()
    {
        return $this->data[$this->position];
    }

    public function getChildren()
    {
        return $this->data[$this->position];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function key()
    {
        return $this->position;
    }

    public function isTree()
    {
        return true;
    }
}
