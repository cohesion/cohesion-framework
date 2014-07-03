<?php
namespace Cohesion\Structure\View;

class JSONView extends DataView {
    const CONTENT_TYPE = 'application/json';

    public function generateView() {
        header('Content-Type: ' . self::CONTENT_TYPE);
        $data = $this->getOutput();
        return json_encode($data);
    }
}
