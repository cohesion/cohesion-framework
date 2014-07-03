<?php

namespace Cohesion\Config;

interface Configurable {
    public function __construct(Config $config);
}
