<?php

class CohesionMoLoader extends MustacheLoader {
	public function __construct($baseDir, $extension = 'html') {
        parent::__construct($baseDir, $extension);
	}
}

