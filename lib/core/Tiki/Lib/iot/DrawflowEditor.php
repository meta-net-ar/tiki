<?php

namespace Tiki\Lib\iot;

class DrawflowEditor
{
    public array $node_definition_instances = [];
    public string $app_id;
    public string $app_uuid;
    public string $app_name;
    public string $editor_id;

    public function __construct(string $app_id, string $app_uuid, $app_name)
    {
        $this->app_id = $app_id;
        $this->app_uuid = $app_uuid;
        $this->editor_id = 'editor' . $app_id;
        $this->app_name = $app_name;
        $this->node_definition_instances = DrawflowProcessor::loadNodeDefinitions($this->app_uuid);
    }

    public function getHtmlLayout(array $config): string
    {
        $nodes = '';
        foreach ($this->node_definition_instances as $nodeDefinition) {
            $nodes .= '<div class="node-container">';
            $nodes .= $nodeDefinition->getTemplate($config);
            $nodes .= '</div>';
        }
        return '<div id="' . $this->app_id . '" class="editor instance">
            <div class="node-wrapper bg-light">' . $nodes . ' </div>
            <div class="dropzone position-relative">
                    <div class="parent-drawflow draw-area" id="draw-area-' . $this->app_id . '"></div>
                    <div class="control-bar position-absolute end-0 bottom-0 mb-4 me-4 bg-white px-3 py-1 rounded" data-editor-id="' . $this->editor_id . '">
                        <i role="button" class="fas fa-search-minus text-dark" drawflow-editor-action="zoomOut"></i>
                        <i role="button" class="fas fa-search text-dark" drawflow-editor-action="zoomReset"></i>
                        <i role="button" class="fas fa-search-plus text-dark" drawflow-editor-action="zoomIn"></i>
                        <i role="button" class="fas fa-x text-danger" drawflow-editor-action="clearEditor"></i>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary px-5 mt-5" drawflow-editor-action="saveDrawing" data-editor-id="' . $this->editor_id . '" data-app-uuid="' . $this->app_uuid . '" data-app-name="' . $this->app_name . '">' . tra("Save") . '</button>
            ';
    }

    public function getEditorScript(string $previous_flow_json): string
    {
        $nodes = 'var ' . $this->editor_id . ' = new Drawflow(document.getElementById("draw-area-' . $this->app_id . '"));';
        $nodes .= $this->editor_id . '.reroute = true;';
        $nodes .= 'drawflowInstances.' . $this->editor_id . ' = ' . $this->editor_id . ';';
        $nodes .= $this->editor_id . '.start();';

        foreach ($this->node_definition_instances as $nodeDefinition) {
            $nodes .= $this->editor_id . '.registerNode("' . $nodeDefinition->node_identifier . '", document.querySelector(".'
                . $nodeDefinition->node_identifier . ':not(.clone)"));';
        }
        $nodes .= 'DrawflowInteractiveZone(' . $this->editor_id . ',"draw-area-' . $this->app_id . '");';
        $nodes .= 'drawflowImports["' . $this->editor_id . '"]={app_name:\'' . $this->app_name . '\',data:' . $previous_flow_json . '};';
        return $nodes;
    }
}
