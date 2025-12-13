<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\TaskQueue;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\TaskQueue\QueueManager;
use Tiki\TaskQueue\Exception\QueueManagerException;
use Tiki\TaskQueue\Tasks\QueuedAbstractTask;
use Tiki\TaskQueue\Tasks\CreateInstanceTask;
use Tiki\TaskQueue\Tasks\RebuildIndexTask;
use Tiki\TaskQueue\Tasks\PdfGenerationTask;
use Tiki\TaskQueue\QueuedTaskSettings;
use TikiDb;

/**
 * Unit tests for QueueManager focusing on CreateInstance, RebuildIndex, and PdfGeneration tasks
 */
class QueueManagerTest extends TestCase
{
    private $queueManager;
    private const TASK_CLASSES_DIR = __DIR__ . '/../../core/TaskQueue/Tasks/';
    private const ABSTRACT_TASK_CLASS = 'Tiki\\TaskQueue\\Tasks\\QueuedAbstractTask';
    private const TASK_QUEUED_SETTINGS_CLASS = 'Tiki\\TaskQueue\\QueuedTaskSettings';

    protected function setUp(): void
    {
        // Try to start session if not already started, but don't fail if it doesn't work
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Set up session data for owner tracking
        $_SESSION['u_info'] = ['id' => 1, 'login' => 'admin'];

        // Creating an instance of QueueManager with the mock dependency
        $this->queueManager = new QueueManager();
    }

    /**
     * Test task class existence and inheritance validation dynamically
     */
    public function testTaskClassExistenceAndInheritanceValidation()
    {
        $taskFiles = glob(self::TASK_CLASSES_DIR . '*.php');

        $baseNamespace = 'Tiki\TaskQueue\Tasks\\';

        // Scan all PHP files in the Tasks directory
        foreach ($taskFiles as $file) {
            $filename = basename($file, '.php');

            // Skip files that start with "Queued" (like QueuedAbstractTask, QueuedTaskInterface)
            if (strpos($filename, 'Queued') === 0) {
                continue;
            }

            // Skip index.php
            if ($filename === 'index') {
                continue;
            }

            $className = $baseNamespace . $filename;

            // Test that the class exists
            $this->assertTrue(
                class_exists($className),
                sprintf('Task class %s should exist', $className)
            );

            // Test that it extends QueuedAbstractTask
            $this->assertTrue(
                is_subclass_of($className, self::ABSTRACT_TASK_CLASS),
                sprintf('Task class %s should extend QueuedAbstractTask', $className)
            );
        }
    }

    /**
     * Test CreateInstanceTask status transition workflow
     */
    public function testCreateInstanceTaskStatusTransitionWorkflow()
    {
        // Test that tasks can have different statuses
        $task = new CreateInstanceTask([]);
        $task->setId(1);

        // Initial status should be null
        $this->assertNull($task->getStatus());

        // Set to pending
        $task->setStatus(QueuedTaskSettings::PENDING);
        $this->assertEquals(QueuedTaskSettings::PENDING, $task->getStatus());

        // Set to in progress
        $task->setStatus(QueuedTaskSettings::IN_PROGRESS);
        $this->assertEquals(QueuedTaskSettings::IN_PROGRESS, $task->getStatus());

        // Set to completed
        $task->setStatus(QueuedTaskSettings::COMPLETED);
        $this->assertEquals(QueuedTaskSettings::COMPLETED, $task->getStatus());

        // Set to failed
        $task->setStatus(QueuedTaskSettings::FAILED);
        $this->assertEquals(QueuedTaskSettings::FAILED, $task->getStatus());
    }

    public function testCreateInstanceQueueTask()
    {
        $params = [];
        $params['params'] = [
            'command' => 'create:instance',
            '--type' => 'blank',
            '--url' => 'http://test.example.com',
            '--name' => 'Test Instance'
        ];
        $params['instance_type'] = 'local';
        $params['profile'] = null;
        $params['repository'] = 'https://github.com/test.git';
        $params['leavepassword'] = true;
        $params['tikipassword'] = 'password';
        $task = new CreateInstanceTask($params);
        $queuedTask = $this->queueManager->queueTask($task);
        $this->assertEquals('CreateInstanceTask', $queuedTask->getType());
        $this->assertEquals('Pending', $queuedTask->getStatus());
        $params = $queuedTask->getParams();
        $params = $params['params'];
        $this->assertEquals('create:instance', $params['command']);
        $this->assertEquals('Test Instance', $params['--name']);
        $this->assertEquals('blank', $params['--type']);
        $this->assertEquals('http://test.example.com', $params['--url']);
        // Clean up: delete the test task
        $this->queueManager->deleteQueuedTask($queuedTask->getId());
    }

    /**
     * Test task property getters and setters
     */
    public function testTaskPropertyManagement()
    {
        $task = new CreateInstanceTask([]);

        // Test ID management
        $task->setId(123);
        $this->assertEquals(123, $task->getId());

        // Test type management
        $this->assertEquals('CreateInstanceTask', $task->getType());

        // Test parameter management
        $newParams = ['test' => 'value'];
        $task->setParams($newParams);
        $this->assertEquals($newParams, $task->getParams());

        // Test result management
        $task->setResult('Task completed successfully');
        $this->assertEquals('Task completed successfully', $task->getResult());
    }

    /**
     * Test task timestamp management
     */
    public function testTaskTimestampManagement()
    {
        $task = new CreateInstanceTask([]);

        $now = date('Y-m-d H:i:s');

        // Test creation timestamp
        $task->setCreatedAt($now);
        $this->assertEquals($now, $task->getCreatedAt());

        // Test start timestamp
        $task->setStartedAt($now);
        $this->assertEquals($now, $task->getStartedAt());

        // Test end timestamp
        $task->setEndedAt($now);
        $this->assertEquals($now, $task->getEndedAt());
    }

    /**
     * Test task to array conversion
     */
    public function testTaskToArrayConversion()
    {
        $task = new CreateInstanceTask([]);
        $task->setId(123);
        $task->setStatus(QueuedTaskSettings::PENDING);
        $task->setResult('Test result');

        $array = $task->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(123, $array['id']);
        $this->assertEquals('CreateInstanceTask', $array['type']);
        $this->assertEquals(QueuedTaskSettings::PENDING, $array['status']);
        $this->assertEquals('Test result', $array['result']);
    }

    /**
     * Test task ID validation
     */
    public function testTaskIdValidation()
    {
        $task = new CreateInstanceTask([]);

        // Test that getting ID without setting it throws exception
        $this->expectException(QueueManagerException::class);
        $this->expectExceptionMessage('Task ID is not set.');
        $task->getId();
    }

    /**
     * Test task output management
     */
    public function testTaskOutputManagement()
    {
        $task = new CreateInstanceTask([]);

        // Test that output is initialized
        $this->assertNotNull($task->getBufferedOutput());
        $this->assertInstanceOf(BufferedOutput::class, $task->getBufferedOutput());

        // Test output verbosity
        $output = $task->getBufferedOutput();
        $this->assertEquals(OutputInterface::VERBOSITY_VERBOSE, $output->getVerbosity());
    }

    /**
     * Test task parameter validation for different task types
     */
    public function testTaskParameterValidation()
    {
        // Test CreateInstanceTask parameters
        $createParams = ['params' => ['command' => 'create:instance']];
        $createTask = new CreateInstanceTask($createParams);
        $this->assertEquals('create:instance', $createTask->getParams()['params']['command']);

        // Test RebuildIndexTask parameters
        $rebuildParams = ['params' => ['command' => 'index:rebuild']];
        $rebuildTask = new RebuildIndexTask($rebuildParams);
        $this->assertEquals('index:rebuild', $rebuildTask->getParams()['params']['command']);

        // Test PdfGenerationTask parameters
        $pdfParams = ['page' => 'Test Page'];
        $pdfTask = new PdfGenerationTask($pdfParams);
        $this->assertEquals('Test Page', $pdfTask->getParams()['page']);
    }

    /**
     * Test task status constants
     */
    public function testTaskStatusConstants()
    {
        // Test that all status constants are defined
        $this->assertTrue(defined(self::TASK_QUEUED_SETTINGS_CLASS . '::PENDING'));
        $this->assertTrue(defined(self::TASK_QUEUED_SETTINGS_CLASS . '::IN_PROGRESS'));
        $this->assertTrue(defined(self::TASK_QUEUED_SETTINGS_CLASS . '::COMPLETED'));
        $this->assertTrue(defined(self::TASK_QUEUED_SETTINGS_CLASS . '::FAILED'));

        // Test constant values
        $this->assertEquals('Pending', QueuedTaskSettings::PENDING);
        $this->assertEquals('InProgress', QueuedTaskSettings::IN_PROGRESS);
        $this->assertEquals('Completed', QueuedTaskSettings::COMPLETED);
        $this->assertEquals('Failed', QueuedTaskSettings::FAILED);
    }

    /**
     * Test task constructor with all parameters
     */
    public function testTaskConstructorWithAllParameters()
    {
        $params = ['test' => 'value'];
        $id = 123;
        $type = 'CustomTask';
        $status = QueuedTaskSettings::PENDING;
        $result = 'Test result';
        $createdAt = '2024-01-01 00:00:00';
        $startedAt = '2024-01-01 01:00:00';
        $endedAt = '2024-01-01 02:00:00';

        $task = new CreateInstanceTask($params, $id, $type, $status, $result, $createdAt, $startedAt, $endedAt);

        $this->assertEquals($params, $task->getParams());
        $this->assertEquals($id, $task->getId());
        $this->assertEquals($type, $task->getType());
        $this->assertEquals($status, $task->getStatus());
        $this->assertEquals($result, $task->getResult());
        $this->assertEquals($createdAt, $task->getCreatedAt());
        $this->assertEquals($startedAt, $task->getStartedAt());
        $this->assertEquals($endedAt, $task->getEndedAt());
    }

    /**
     * Test that all task classes implement required interface methods
     */
    public function testTaskInterfaceCompliance()
    {
        $taskFiles = glob(self::TASK_CLASSES_DIR . '*.php');
        $baseNamespace = 'Tiki\TaskQueue\Tasks\\';

        foreach ($taskFiles as $file) {
            $filename = basename($file, '.php');

            if (strpos($filename, 'Queued') === 0 || $filename === 'index') {
                continue;
            }

            $className = $baseNamespace . $filename;
            $task = new $className([]);

            // Test that all required interface methods exist
            $this->assertTrue(method_exists($task, 'execute'));
            $this->assertTrue(method_exists($task, 'getType'));
            $this->assertTrue(method_exists($task, 'getParams'));
            $this->assertTrue(method_exists($task, 'setParams'));
            $this->assertTrue(method_exists($task, 'getId'));
            $this->assertTrue(method_exists($task, 'setId'));
            $this->assertTrue(method_exists($task, 'getStatus'));
            $this->assertTrue(method_exists($task, 'setStatus'));
        }
    }

    /**
     * Test task error handling
     */
    public function testTaskErrorHandling()
    {
        $task = new CreateInstanceTask([]);

        // Test that setting invalid status doesn't break the task
        $task->setStatus('InvalidStatus');
        $this->assertEquals('InvalidStatus', $task->getStatus());

        // Test that setting null values works
        $task->setStatus(null);
        $this->assertNull($task->getStatus());

        $task->setResult(null);
        $this->assertNull($task->getResult());
    }
}
