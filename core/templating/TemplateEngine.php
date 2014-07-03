<?php
namespace Cohesion\Templating;

interface TemplateEngine {
    public function render($template, $vars);

    public function renderFromFile($templateFileName, $vars);
}
