<?php

interface TemplateEngine {
    public function render($template, $vars);

    public function renderFromFile($templateFileName, $vars);
}
