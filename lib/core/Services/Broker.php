<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class Services_Broker
{
    private $container;
    private $extensionPackage;

    public function __construct($container, $extensionPackage = '')
    {
        $this->container = $container;
        $this->extensionPackage = $extensionPackage;
    }

    public function process($controller, $action, JitFilter $request)
    {
        $access = TikiLib::lib('access');

        try {
            $this->preExecute();

            $output = $this->attemptProcess($controller, $action, $request);

            if (isset($output['FORWARD'])) {
                if (is_array($output['FORWARD'])) {
                    $output['FORWARD'] = array_merge(
                        [
                            'controller' => $controller,
                            'action' => $action,
                        ],
                        $output['FORWARD']
                    );
                } else {
                    $output['FORWARD'] = (is_string($output['FORWARD']) && ! empty($output['FORWARD'])) ? $output['FORWARD'] : null;
                }
            }

            if ($access->is_serializable_request()) {
                if (TIKI_API) {
                    TikiLib::lib('logs')->api_add_action();
                }
                echo $access->output_serialized($output);
            } else {
                TikiLib::events()->trigger('tiki.process.render');
                echo $this->render($controller, $action, $output, $request);
            }
        } catch (Services_Exception_FieldError $e) {
            if ($request->modal->int() && $access->is_xml_http_request()) {
                // Special handling for modal dialog requests
                // Do not send an error code as bootstrap will just blank out
                // Render the error as a modal
                $smarty = TikiLib::lib('smarty');
                $smarty->assign('title', tr('Oops'));
                $smarty->assign('detail', ['message' => $e->getMessage()]);
                $smarty->assign('global_extend_layout', 'layouts/internal/layout_empty.tpl');
                $smarty->display("extends:internal/modal.tpl|error-ajax.tpl");
            } else {
                if (TIKI_API) {
                    TikiLib::lib('logs')->api_add_action($e->getMessage(), $e->getCode());
                }
                $access->display_error(null, $e->getMessage(), $e->getCode());
            }
        } catch (Exception $e) {
            if ($request->modal->int() && $access->is_xml_http_request()) {
                // Special handling for modal dialog requests
                // Do not send an error code as bootstrap will just blank out
                // Render the error as a modal
                $smarty = TikiLib::lib('smarty');
                $smarty->assign('title', tr('Oops'));
                $smarty->assign('detail', ['message' => $e->getMessage()]);
                $smarty->assign('global_extend_layout', 'layouts/internal/layout_empty.tpl');
                $smarty->display("extends:internal/modal.tpl|error-ajax.tpl");
            } else {
                if (TIKI_API) {
                    TikiLib::lib('logs')->api_add_action($e->getMessage(), $e->getCode());
                }
                $access->display_error(null, $e->getMessage(), $e->getCode());
            }
        }
    }

    public function internal($controller, $action, $request = [])
    {
        if (! $request instanceof JitFilter) {
            $request = new JitFilter($request);
        }

        return $this->attemptProcess($controller, $action, $request);
    }

    public function internalRender($controller, $action, $request)
    {
        if (! $request instanceof JitFilter) {
            $request = new JitFilter($request);
        }

        $output = $this->internal($controller, $action, $request);
        return $this->render($controller, $action, $output, $request, true);
    }

    private function attemptProcess($controller, $action, $request)
    {
        try {
            if ($this->extensionPackage) {
                $handler = $this->container->get("package.controller." . $this->extensionPackage . ".$controller");
            } else {
                $handler = $this->container->get("tiki.controller.$controller");
            }

            $actionParts = explode('_', $action);
            $method = 'action';

            foreach ($actionParts as $part) {
                $method .= ucfirst(strtolower($part));
            }

            $actionExists = method_exists($handler, $method);

            if (! $actionExists) {
                $method = 'action_' . $action;
                $actionExists = method_exists($handler, $method);
            }

            if ($actionExists) {
                if (method_exists($handler, 'getSection')) {
                    $banningOnly = true;
                    $ajaxRequest = true;
                    $section = $handler->getSection();
                    include_once('tiki-section_options.php');
                }
                if (method_exists($handler, 'setUp')) {
                    $handler->setUp();
                }

                return $handler->$method($request);
            } else {
                throw new Services_Exception(tr('Action not found (%0 in %1)', $action, $controller), 404);
            }
        } catch (ServiceNotFoundException $e) {
            throw new Services_Exception(tr('Controller not found (%0)', $controller), 404);
        }
    }

    private function preExecute()
    {
        $access = TikiLib::lib('access');

        if ($access->is_xml_http_request() && ! $access->is_serializable_request()) {
            $headerlib = TikiLib::lib('header');
            $headerlib->clear_js(true); // Only need the partials
        }
    }

    private function render($controller, $action, $output, JitFilter $request, $internal = false)
    {
        if (isset($output['FORWARD'])) {
            $url = TikiLib::lib('service')->getUrl($output['FORWARD']);
            TikiLib::lib('access')->redirect($url);
        }

        if (! empty($output['override_action'])) {
            $action = $output['override_action'];
        }

        $smarty = TikiLib::lib('smarty');

        $template = "$controller/$action.tpl";

        //if template doesn't exists, simply return the array given from the action
        //if noTemplate is specified in the query string, it will skip the template
        if (! $smarty->templateExists($template) || strpos($_SERVER['QUERY_STRING'], '&noTemplate') !== false) {
            header('Content-Type: application/json');
            return json_encode($output);
        }

        $access = TikiLib::lib('access');
        if ($output != null) {
            foreach ($output as $key => $value) {
                $smarty->assign($key, $value);
            }
        }

        $layout = null;

        if ($internal) {
            $layout = "layouts/internal/layout_view.tpl";
        } elseif ($layout = $request->modal->int() || $access->is_xml_http_request()) {
            $layout = $request->modal->int()
                ? 'layouts/internal/modal.tpl'
                : 'layouts/internal/ajax.tpl';
        }

        if ($layout) {
            $smarty->assign('global_extend_layout', 'layouts/internal/layout_empty.tpl');
            $content = $smarty->fetch("extends:$layout|$template");
            $smarty->clear_assign('global_extend_layout');
            return $content;
        } else {
            return $smarty->fetch($template);
        }
    }
}
