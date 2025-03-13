<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\Question;
use TikiLib;

#[AsCommand(
    name: 'sbom:generate',
    description: 'Generate a Software Bill of Materials (SBOM) for Tiki',
)]
class SBOMGenerateCommand extends Command
{
    protected function verifyUserPermission(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the username: ');
        $username = $helper->ask($input, $output, $question);

        $passwordQuestion = new Question('Please enter the password: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $passwordQuestion);

        $userslib = TikiLib::lib('user');

        $result = $userslib->validate_user($username, $password);

        if ($result[2] !== USER_VALID) {
            $output->writeln('<error>Permission denied</error>');
            return false;
        }
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->verifyUserPermission($input, $output)) {
            return Command::FAILURE;
        }

        $output->writeln('Generating SBOM...');

        $nodeDeps = $this->getNodeDependencies($output);
        $composerDeps = $this->getComposerDependencies($output);

        $packages = array_merge(array_map(function ($dep) {
            if (isset($dep['workspace'])) {
                $dep['direct'] = true;
            } else {
                $dep['direct'] = false;
            }
            $dep['type'] = 'npm';
            return $dep;
        }, $nodeDeps), array_map(function ($dep) {
            if (isset($dep['usage'])) {
                $dep['direct'] = true;
            } else {
                $dep['direct'] = false;
            }
            $dep['type'] = 'composer';
            return $dep;
        }, $composerDeps));

        $spdxStandardFormattedPackages = array_map(function ($package) {
            $nonStandardFields = ['usage', 'requiredBy', 'workspace', 'usageComment', 'description', 'direct', 'error', 'type', 'latestVersion', 'lastPublished', 'fileCount', 'unpackedSize'];
            foreach ($nonStandardFields as $field) {
                if (isset($package[$field])) {
                    $package['x-' . $field] = $package[$field];
                    unset($package[$field]);
                }
            }

            $nameMapping = [
                'version' => 'versionInfo',
                'downloadUrl' => 'downloadLocation',
                'license' => 'licenseDeclared',
                'url' => 'homepage',
                'sourceUrl' => 'sourceInfo',
            ];
            foreach ($nameMapping as $oldName => $newName) {
                if (isset($package[$oldName])) {
                    $package[$newName] = $package[$oldName];
                    unset($package[$oldName]);
                }
            }

            return $package;
        }, $packages);

        file_put_contents('sbom.json', json_encode([
            'SPDXID' => 'SPDXRef-DOCUMENT',
            'spdxVersion' => 'SPDX-2.3',
            'creationInfo' => [
                'created' => date('c'),
                'creators' => ["Tool: php console.php sbom:generate"],
            ],
            'name' => 'Tiki',
            'documentNamespace' => 'https://tiki.org/document' . '-' . gmdate("Ymd\THis\Z") . "-" . uniqid(),
            'packages' => $spdxStandardFormattedPackages,
        ], JSON_PRETTY_PRINT));

        $output->writeln('SBOM generated successfully.');

        return Command::SUCCESS;
    }

    protected function getComposerDependencies($output)
    {
        $composerPackages = shell_exec('composer -d vendor_bundled show --tree --format=json');
        $composerPackagesData = json_decode($composerPackages, true)['installed'];
        $composerLockData = json_decode(file_get_contents('composer.lock'), true);


        $deps = [];

        $output->writeln('Processing composer packages... ');

        $progressBar = new ProgressBar($output, count($composerPackagesData));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        foreach ($composerPackagesData as $package) {
            $dep = [
                'SPDXID' => 'SPDXRef-Package-' . hash('sha256', $package['name'] . $package['version']),
                'name' => $package['name'],
                'version' => $package['version'],
                'usage' => $this->getComposerPackageUsage($package['name']),
            ];

            $progressBar->setMessage("Getting metadata for $package[name] ...");
            $progressBar->display();
            $dep = array_merge($dep, $this->getComposerPackageMetadata($package['name']));

            $deps[] = $dep;
            $deps = array_merge($deps, $this->getComposerSubPackages($package['name'], $package, $composerLockData));
            $progressBar->advance();
        }

        $progressBar->finish();

        $deps = $this->groupSameSubPackageDistinctParents($deps);

        echo "\n";

        return $deps;
    }

    protected function getNodeDependencies($output)
    {
        $npmPackages = shell_exec('npm list --json --workspaces --all --long --include-workspace-root');
        $npmPackagesData = json_decode($npmPackages, true);

        $rootPackageJsonContent = json_decode(file_get_contents('package.json'), true);

        $depsComments = $rootPackageJsonContent['devDepencenciesCommentWillAlsoBeAvailableAsDevDependenciesInWorkspace'];
        $deps = [];

        $output->writeln('Processing npm packages... ');
        $progressBar = new ProgressBar($output, count($npmPackagesData['dependencies']));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        foreach ($npmPackagesData['dependencies'] as $workspace => $packages) {
            if (is_dir(glob('src/{*,*/*,*/*/,*/*/*/}' . $workspace, GLOB_BRACE)[0])) {
                $packageJsonfilePath = glob('src/{*,*/*,*/*/,*/*/*/}' . $workspace . '/package.json', GLOB_BRACE)[0];
                $packageJsonfileContent = $packageJsonfilePath ? file_get_contents($packageJsonfilePath) : null;
                $depsComments = array_merge($depsComments, json_decode($packageJsonfileContent, true)['devDependenciesComment'] ?? []);

                foreach ($packages['dependencies'] as $name => $info) {
                    $dep = [
                        'SPDXID' => 'SPDXRef-Package-' . hash('sha256', $name . $info['version']),
                        'name' => $name,
                        'version' => $info['version'],
                        'downloadUrl' => $info['resolved'],
                        'license' => $info['license'],
                        'description' => $info['description'] ?? null,
                        'usageComment' => $depsComments[$name] ?? null,
                        'workspace' => $workspace,
                        'usage' => $this->getNodePackageUsage($name),
                    ];

                    $progressBar->setMessage("Getting metadata for $name ...");
                    $progressBar->display();
                    $dep = array_merge($dep, $this->getNodePackageMetadata($name));

                    $deps[] = $dep;
                    $deps = array_merge($deps, $this->getNodeSubPackages($name, $info));
                }
            } else {
                $dep = [
                    'SPDXID' => 'SPDXRef-Package-' . hash('sha256', $workspace . $packages['version']),
                    'name' => $workspace,
                    'version' => $packages['version'],
                    'downloadUrl' => $packages['resolved'],
                    'license' => $packages['license'],
                    'description' => $info['description'] ?? null,
                    'usageComment' => $depsComments[$workspace] ?? null,
                    'workspace' => 'root',
                    'usage' => $this->getNodePackageUsage($workspace),
                ];

                $progressBar->setMessage("Getting metadata for $workspace ...");
                $progressBar->display();
                $dep = array_merge($dep, $this->getNodePackageMetadata($workspace));

                $deps[] = $dep;
                $deps = array_merge($deps, $this->getNodeSubPackages($workspace, $packages));
            }
            $progressBar->advance();
        }

        $deps = $this->groupSameSubPackageDistinctParents($deps);
        $deps = $this->groupSamePackageDistinctCommentsAndWorkspaces($deps);

        $progressBar->finish();

        echo "\n";

        return $deps;
    }

    protected function getNodeSubPackages($parentName, $packageInfo, &$deps = [])
    {
        if (empty($packageInfo['dependencies']) || ! isset($packageInfo['dependencies'])) {
            return $deps;
        }

        foreach ($packageInfo['dependencies'] as $name => $info) {
            $dep = [
                'SPDXID' => 'SPDXRef-Package-' . hash('sha256', $name . $info['version']),
                'name' => $name,
                'version' => $info['version'],
                'homepage' => $info['homepage'],
                'downloadUrl' => $info['resolved'],
                'license' => $info['license'] ?? null,
                'subPackageOf' => $parentName,
            ];

            $deps[] = $dep;

            return $this->getNodeSubPackages($name, $info, $deps);
        }
    }

    protected function groupSameSubPackageDistinctParents($deps)
    {
        $reduced = array_reduce($deps, function ($groupedDeps, $dep) {
            $key = $dep['name'] . $dep['version'] . ($dep['workspace'] ?? '');
            if (! isset($dep['subPackageOf'])) {
                $groupedDeps[$key] = $dep;
                return $groupedDeps;
            }

            if (! isset($groupedDeps[$key])) {
                $groupedDeps[$key] = [];
            }
            if (! isset($groupedDeps[$key]['requiredBy'])) {
                $groupedDeps[$key]['requiredBy'] = [];
            }
            $groupedDeps[$key] = array_merge($dep, $groupedDeps[$key]);
            if (! in_array($dep['subPackageOf'], $groupedDeps[$key]['requiredBy'])) {
                $groupedDeps[$key]['requiredBy'][] = $dep['subPackageOf'];
            }
            unset($groupedDeps[$key]['subPackageOf']);

            return $groupedDeps;
        }, []);

        return array_values($reduced);
    }

    protected function groupSamePackageDistinctCommentsAndWorkspaces($deps)
    {
        $reduced = array_reduce($deps, function ($groupedDeps, $dep) {
            $key = $dep['name'] . $dep['version'];
            if (! isset($groupedDeps[$key])) {
                $groupedDeps[$key] = $dep;
                return $groupedDeps;
            } else {
                if ($dep['usageComment'] !== $groupedDeps[$key]['usageComment']) {
                    $workspace1 = $dep['workspace'] ?? '';
                    $workspace2 = $groupedDeps[$key]['workspace'] ?? '';
                    if ($workspace1 && isset($dep['usageComment'])) {
                        if (is_array($groupedDeps[$key]['usageComment'])) {
                            $groupedDeps[$key]['usageComment'] = array_merge($groupedDeps[$key]['usageComment'], ["workspace-$workspace1" => $dep['usageComment']]);
                        } else {
                            $groupedDeps[$key]['usageComment'] = [
                                "workspace-$workspace2" => $groupedDeps[$key]['usageComment'],
                                "workspace-$workspace1" => $dep['usageComment'],
                            ];
                        }
                    }
                }
                if ($dep['workspace'] !== $groupedDeps[$key]['workspace']) {
                    if (isset($dep['workspace'])) {
                        if (is_array($groupedDeps[$key]['workspace'])) {
                            $groupedDeps[$key]['workspace'] = array_merge($groupedDeps[$key]['workspace'], [$dep['workspace']]);
                        } else {
                            $groupedDeps[$key]['workspace'] = [$groupedDeps[$key]['workspace'], $dep['workspace']];
                        }
                    }
                }
                $groupedDeps[$key] = array_merge($dep, $groupedDeps[$key]);
            }

            return $groupedDeps;
        }, []);

        return array_values($reduced);
    }

    protected function getNodePackageMetadata($name)
    {
        $metadata = json_decode(shell_exec('npm info ' . $name . ' --json'), true);
        $lastVersion = end($metadata['versions']);

        return [
            'url' => $metadata['homepage'],
            'sourceUrl' => $metadata['repository']['url'],
            'latestVersion' => $lastVersion,
            'lastPublished' => $metadata['time'][$lastVersion],
            'fileCount' => $metadata['dist']['fileCount'],
            'unpackedSize' => $metadata['dist']['unpackedSize'] / 1000 . 'KB',
        ];
    }

    protected function getNodePackageUsage($packageName)
    {
        $exludeDir = "--exclude-dir={node_modules,dist,public,temp,vendor,language,lang,db,doc,.git,vendor,.idea,.gitpod,coverage}";

        $jsReferences = shell_exec('grep -r -e "import \"' . $packageName . '\"" -e "import \'' . $packageName . '\'" -e "from \"' . $packageName . '\"" -e "from \'' . $packageName . '\'" -e "from \'' . $packageName . '/.*\'" -e "from \"' . $packageName . '/.*\"" ' . $exludeDir . ' --exclude="*.json" --exclude="*.min.js" --exclude="*.map" --exclude="*.md" ./');

        $scssReferences = shell_exec('grep -r "node_modules/' . $packageName . '" --include="*.scss" ' . $exludeDir . ' ./');

        $phpReferences = shell_exec('grep -r -e "vendor_dist/' . $packageName . '/" -e "vendor_dist' . '/' . $packageName . '\'" -e "vendor_dist' . '/' . $packageName . '\"" -e "NODE_PUBLIC_DIST_PATH . \"/' . $packageName . '\"" -e "NODE_PUBLIC_DIST_PATH . \'/' . $packageName . '\'" -e "NODE_PUBLIC_DIST_PATH . \'/' . $packageName . '/' . '" -e "NODE_PUBLIC_DIST_PATH . \"/' . $packageName . '/" --include="*.php" --include="*.tpl" ' . $exludeDir . ' ./');

        $files = $jsReferences . $scssReferences . $phpReferences;

        $files = array_filter(explode("\n", $files), function ($file) {
            return ! empty($file);
        });

        return $files;
    }

    protected function getComposerPackageMetadata($name)
    {
        $url = "https://repo.packagist.org/p2/$name.json";
        $fileContent = file_get_contents($url);
        if (! $fileContent) {
            return ["error" => "Package registry not found"];
        }

        $data = json_decode($fileContent, true)['packages'][$name];
        $latestRelease = reset($data);

        if (! $latestRelease) {
            return ["error" => "No release found"];
        }

        return [
            'url' => $latestRelease['homepage'],
            'sourceUrl' => $latestRelease['source']['url'],
            'downloadUrl' => $latestRelease['dist']['url'],
            'latestVersion' => $latestRelease['version'],
            'lastPublished' => $latestRelease['time'],
            'license' => implode(',', $latestRelease['license']),
            'description' => $latestRelease['description'],
        ];
    }

    protected function getComposerSubPackages($parentName, $packageInfo, $composerLockData, &$deps = [])
    {
        if (empty($packageInfo['requires']) || ! isset($packageInfo['requires'])) {
            return $deps;
        }

        foreach ($packageInfo['requires'] as $package) {
            $name = $package['name'];
            $version = $package['version'];
            if (substr($name, 0, 4) === 'ext-' || $name === 'php') {
                unset($packageInfo['requires'][$name]);
            } else {
                $lockedPackage = $composerLockData['packages'][array_search($name, array_column($composerLockData['packages'], 'name'))];
                $deps[] = [
                    'SPDXID' => 'SPDXRef-Package-' . hash('sha256', $name . $version),
                    'name' => $name,
                    'version' => $lockedPackage['version'],
                    'subPackageOf' => $parentName,
                ];
            }

            return $this->getComposerSubPackages($name, $package, $composerLockData, $deps);
        }
    }

    protected function getComposerPackageUsage($packageName)
    {
        $composerJson = json_decode(file_get_contents("vendor_bundled/vendor/$packageName/composer.json"), true);
        $autoload = $composerJson['autoload'] ?? [];
        $packageNamespace = array_keys(($autoload['psr-4'] ?? $autoload['psr-0']) ?? [''])[0];

        $namespaceMatchPattern = '';
        $generateNamespaceMatchPattern = fn ($namespace) => '-e "use ' . $namespace . '" -e "new ' . $namespace . '(" -e "new ' . $namespace . '\\\\\\\\" -e "' . $namespace . '::" -e "' . $namespace . '\\\\\\\\\.*" -e "extends ' . $namespace . '" -e "implements ' . $namespace . '" ';

        if ($packageNamespace) {
            $namespace = rtrim($packageNamespace, '\\');
            $namespaceQuery = str_replace('\\', '\\\\\\\\', $namespace);
            $namespaceMatchPattern = $generateNamespaceMatchPattern($namespaceQuery);
        } elseif (isset($autoload['classmap'])) {
            $classes = [];
            foreach ($autoload['classmap'] as $class) {
                if (! is_dir("vendor_bundled/vendor/$packageName/$class") && file_exists("vendor_bundled/vendor/$packageName/$class")) {
                    $classes = array_merge($classes, $this->getClassesFromFile("vendor_bundled/vendor/$packageName/$class"));
                }
                foreach (glob("vendor_bundled/vendor/$packageName/$class*.php") as $file) {
                    $classes = array_merge($classes, $this->getClassesFromFile($file));
                }
            }
            array_unique($classes);
            foreach ($classes as $class) {
                $namespaceMatchPattern .= $generateNamespaceMatchPattern($class);
            }
        } elseif (isset($autoload['files'])) {
            $functions = [];
            $classes = [];
            foreach ($autoload['files'] as $file) {
                $functions = array_unique(array_merge($functions, $this->getFunctionsFromFile("vendor_bundled/vendor/$packageName/$file")));
                $classes = array_unique(array_merge($classes, $this->getClassesFromFile("vendor_bundled/vendor/$packageName/$file")));
            }
            foreach ($functions as $function) {
                if (empty($function)) {
                    continue;
                }
                $namespaceMatchPattern .= '-e "[ =]' . $function . '(" ';
            }
            foreach ($classes as $class) {
                $namespaceMatchPattern .= $generateNamespaceMatchPattern($class);
            }
        }

        $files = shell_exec('grep -r -e "vendor_bundled/vendor/' . $packageName . '" ' . $namespaceMatchPattern . ' --exclude-dir={vendor,schema,composer-patches,themes,lang,temp} --include="*.php" ./');
        $files = array_filter(explode("\n", $files), function ($file) {
            return ! empty($file);
        });

        if (strpos($packageName, 'npm-asset') !== false) {
            echo $packageName . "\n";
            $tplReferenes = shell_exec('grep -r -e "src=\"vendor_bundled/vendor/' . $packageName . '/.*\"" -e "src=\'vendor_bundled/vendor/' . $packageName . '.*\'" -e "href=\"vendor_bundled/vendor/' . $packageName . '.*\"" -e "href=\'vendor_bundled/vendor/' . $packageName . '.*\'" --exclude-dir={vendor,schema,composer-patches,themes,lang,temp} --include="*.tpl" ./');
            $tplReferenes = array_filter(explode("\n", $tplReferenes), function ($file) {
                return ! empty($file);
            });
            $files = array_merge($files, $tplReferenes);
        }

        return $files;
    }

    protected function getClassesFromFile($filePath)
    {
        $classes = [];
        $namespace = '';
        $tokens = token_get_all(file_get_contents($filePath));
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NS_SEPARATOR || $tokens[$j][0] === T_NAME_QUALIFIED) {
                        $namespace .= $tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }

                if (! empty($namespace)) {
                    $classes[] = str_replace('\\', '\\\\\\\\', $namespace);
                    break;
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '{') {
                        $classes[] = $tokens[$i + 2][1];
                        break;
                    }
                }
            }
        }
        return $classes;
    }

    protected function getFunctionsFromFile($filePath)
    {
        $functions = [];
        $tokens = token_get_all(file_get_contents($filePath));
        $count = count($tokens);
        $wrapper = 0;
        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === '{') {
                $wrapper += 1;
            } elseif ($tokens[$i][0] === '}') {
                $wrapper -= 1;
            }

            if ($tokens[$i][0] === T_FUNCTION) {
                if ($wrapper !== 0) {
                    continue;
                }
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '(') {
                        $functions[] = $tokens[$i + 2][1];
                        break;
                    }
                }
            }
        }
        return $functions;
    }
}
