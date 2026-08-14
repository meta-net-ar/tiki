<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Registry\Container;
use Mcp\Server as McpServer;
use Mcp\Server\Session\SessionStoreInterface;

/**
 * Thin wrapper that builds an MCP Server from Tiki tool instances.
 *
 * Uses explicit tool registration (no directory scanning) by reading
 * #[McpTool] attributes from the provided tool class instances.
 * Instances are registered in the SDK's PSR-11 container so the
 * builder can resolve [ClassName::class, 'method'] handlers.
 */
class Server
{
    /**
     * Build an MCP server with the given tool instances registered.
     *
     * Each public method annotated with #[McpTool] on the provided
     * instances will be registered as an MCP tool.
     *
     * @param string $name    Server name
     * @param string $version Server version
     * @param array  $toolInstances Objects whose #[McpTool] methods become tools
     * @param SessionStoreInterface|null $sessionStore Session store (required for HTTP transport)
     * @return McpServer
     */
    public static function create(
        string $name,
        string $version,
        array $toolInstances,
        ?SessionStoreInterface $sessionStore = null
    ): McpServer {
        $container = new Container();

        // Pre-register each tool instance in the container so the SDK
        // can resolve [ClassName::class, 'method'] handlers.
        foreach ($toolInstances as $instance) {
            $container->set(get_class($instance), $instance);
        }

        $builder = McpServer::builder()
            ->setServerInfo($name, $version, 'Tiki Wiki CMS content management')
            ->setContainer($container);

        if ($sessionStore) {
            $builder->setSession($sessionStore);
        }

        foreach ($toolInstances as $instance) {
            $className = get_class($instance);
            $ref = new \ReflectionClass($instance);
            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $attrs = $method->getAttributes(McpTool::class);
                if (empty($attrs)) {
                    continue;
                }

                $attr = $attrs[0]->newInstance();
                $builder->addTool(
                    [$className, $method->getName()],
                    $attr->name,
                    $attr->description,
                    $attr->annotations
                );
            }
        }

        return $builder->build();
    }
}
