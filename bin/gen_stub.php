#!/usr/bin/env php
<?php
if (PHP_SAPI !== 'cli') {
    exit(255);
}
if (!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    echo "Please run composer install first\n";
    exit(255);
}
if (!file_exists(dirname(__DIR__) . '/vendor/squizlabs/php_codesniffer/autoload.php')) {
    echo "Php code sniffer is not installed\n";
    exit(255);
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/squizlabs/php_codesniffer/autoload.php';
$runner = new PHP_CodeSniffer\Runner();

// only support on php 8.0 or higher
//if (PHP_VERSION_ID < 80000) {
//    echo "This script only support on PHP 8.0 or higher\n";
//    exit(255);
//}
$isLoaded = preg_grep('/^ionCube/i', get_loaded_extensions());
// check if ionCube loader is enabled
if (!$isLoaded) {
    echo "IonCube loader is enabled, please disable it to run this script\n";
    exit(255);
}
$_argv = $argv;
$args = [];
$scriptName = array_shift($_argv);
$original_cwd = getcwd();
$cwd = dirname(__DIR__);
chdir($cwd);

$currentKey = null;
foreach ($_argv as $arg) {
    if (strpos($arg, '--') === 0) {
        $arg = substr($arg, 2);
        $arg = explode('=', $arg, 2);
        $key = $arg[0];
        $value = $arg[1] ?? null;
        if (!isset($args[1])) {
            $currentKey = $key;
        }
        $args[$key] = '';
        continue;
    } elseif (strpos($arg, '-') === 0) {
        $arg = substr($arg, 1);
        $args[$arg] = '';
        continue;
    }
    if ($currentKey) {
        $args[$currentKey] = $arg;
        $currentKey = null;
    }
}
$vendorDir = $args['whmcs-dir'] ?? $args['whmcs'] ?? null;
if (!$vendorDir) {
    echo "\033[31mError: whmcs-dir is required\033[0m\n";
    exit;
}
$vendorDir = $vendorDir . '/vendor';
if (DIRECTORY_SEPARATOR === '\\') {
    $vendorDir = str_replace('/', '\\', $vendorDir);
    if (preg_match('/^(\.\\\\|[^A-Z]+:\\\)/', $vendorDir)) {
        $vendorDir = $cwd . '\\' . $vendorDir;
    }
} else {
    if (preg_match('/^(\.\/|[^\/])/', $vendorDir)) {
        $vendorDir = $cwd . '/' . $vendorDir;
    }
}
if (!file_exists($vendorDir)) {
    echo "\033[31mError: whmcs vendor is not exists\033[0m\n";
    exit;
}
if (!is_dir($vendorDir)) {
    echo "\033[31mError: whmcs vendor is not a directory\033[0m\n";
    exit;
}
$stubDIr = $args['stub-dir'] ?? $cwd . '/stubs';
if (DIRECTORY_SEPARATOR === '\\') {
    $stubDIr = str_replace('/', '\\', $stubDIr);
    if (preg_match('/^(\.\\\\|[^A-Z]+:\\\)/', $stubDIr)) {
        $stubDIr = $cwd . '\\' . $stubDIr;
    }
} else {
    if (preg_match('/^(\.\/|[^\/])/', $stubDIr)) {
        $stubDIr = $cwd . '/' . $stubDIr;
    }
}
$autoloadFile = $vendorDir . '/autoload.php';
if (!file_exists($autoloadFile)) {
    echo "\033[31mError: vendor-dir is not a valid composer vendor directory\033[0m\n";
    exit;
}
if (!is_file($autoloadFile)) {
    echo "\033[31mError: vendor-dir is not a valid composer vendor directory\033[0m\n";
    exit;
}
$initFile = dirname($vendorDir) . '/init.php';
if (!file_exists($initFile)) {
    echo "\033[31mError: WHMCS Init file is not exists\033[0m\n";
    exit;
}
$baseDirectory = '/whmcs/whmcs-foundation/lib';
$path = $vendorDir . $baseDirectory;
$targetDirectory = $stubDIr . $baseDirectory;

if (!is_dir($path)) {
    echo "\033[31mError: vendor-dir does not contain whmcs-foundation\033[0m\n";
    exit;
}
$vendorDir = file_exists($vendorDir) ? (realpath($vendorDir) ?: $vendorDir) : $vendorDir;
$stubDIr = file_exists($stubDIr) ? (realpath($stubDIr) ?: $stubDIr) : $stubDIr;
$args['stub-dir'] = $stubDIr;
$args['vendor-dir'] = $vendorDir;
$isQuiet = isset($args['quiet']);
$verbose = $args['verbose'] ?? '';
$verboseLevel = $verbose === '' ? 1 : (is_numeric($verbose) ? (int)$verbose : 1);

$nameSpace = 'WHMCS';
$whmcsFilesTarget = [];
$whmcsFilesSource = [];
$dir = new SplFileInfo($path);
$recursive = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir),
    RecursiveIteratorIterator::SELF_FIRST
);
$basePath = $dir->getRealPath();

function normalizeExport($value): string
{
    if ($value === null) {
        return 'null';
    }
    if ($value === true) {
        return 'true';
    }
    if ($value === false) {
        return 'false';
    }
    if (is_string($value)) {
        return var_export($value, true);
    }
    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }
    $_4spaces = '    ';
    $export = var_export($value, true);
    if (is_array($value)) {
        // is increment array
        $lastKey = null;
        $isIncrement = true;
        $isInvalid = false;
        $loopOnlyArrayScalarAndNull = static function ($value) use (&$isInvalid, &$loopOnlyArrayScalarAndNull) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $loopOnlyArrayScalarAndNull($val);
                    if ($isInvalid) {
                        break;
                    }
                }
            } elseif (!is_scalar($value) && !is_null($value)) {
                $isInvalid = true;
            }
        };
        foreach ($value as $key => $val) {
            $loopOnlyArrayScalarAndNull($val);
            if ($isInvalid) {
                return '[]';
            }
            if (!is_int($key)) {
                $isIncrement = false;
                break;
            }
            if ($lastKey !== null && $key !== $lastKey + 1) {
                $isIncrement = false;
                break;
            }
        }
        if ($isIncrement) {
            $export = preg_replace('~^\s+[0-9]+\s*=>\s~sm', '', $export);
        }
        $export = preg_replace('/^\s*([^)])/ms', $_4spaces . $_4spaces . '$1', $export);
        $export = preg_replace('/^\s*([)])/ms', $_4spaces . '$1', $export);
        $export = preg_replace('~(^|\s)array\s+\(~', '$1array(', $export);
        $export = preg_replace('~^\s*array\(\s*\)\s*$~', 'array()', $export);
    }
    return trim($export);
}

require $initFile;
require $autoloadFile;

$argv = [
    $scriptName,
    $stubDIr,
];
$args = array_filter($args, function ($value) {
    return in_array($value, ['v', 'vv', 'vvv', 'q', 'quiet', 'f', 'force']);
}, ARRAY_FILTER_USE_KEY);

if (isset($args['q']) || isset($args['quiet'])) {
    $argv[] = '-q';
} elseif (isset($args['vvv'])) {
    $argv[] = '-vvv';
} elseif (isset($args['vv'])) {
    $argv[] = '-vv';
} elseif (isset($args['v'])) {
    $argv[] = '-v';
}
$isForce = isset($args['f']) || isset($args['force']);
$isQuiet = in_array('-q', $argv);
$isVerbose = !$isQuiet && (in_array('-v', $argv) || in_array('-vv', $argv) || in_array('-vvv', $argv));
$isVerboseVerbose = !$isQuiet && (in_array('-vv', $argv) || in_array('-vvv', $argv));
$isVeryVerbose = !!$isQuiet && (in_array('-vvv', $argv));

function echoing($message)
{
    global $isQuiet;
    if (!$isQuiet) {
        echo $message;
    }
}

function echoVerbose($message)
{
    global $isVerbose;
    if ($isVerbose) {
        echo $message;
    }
}

function echoVerboseVerbose($message)
{
    global $isVerboseVerbose;
    if ($isVerboseVerbose) {
        echo $message;
    }
}

function echoVeryVerbose($message)
{
    global $isVeryVerbose;
    if ($isVeryVerbose) {
        echo $message;
    }
}

function deleteStub()
{
    global $stubDIr;
    $dir = new RecursiveDirectoryIterator($stubDIr, FilesystemIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($stubDIr);
}

/**
 * @throws ReflectionException
 */
function genStubFromClassName($className): ?string
{
    global $whmcsFilesSource;

    $ref = new ReflectionClass($className);
    if ($ref->getFileName() !== $whmcsFilesSource[$className]) {
        throw new RuntimeException(
            "Class {$className} is not found in {$whmcsFilesSource[$className]}"
        );
    }

    $php = '';
    $namespace = $ref->getNamespaceName();
    if ($namespace) {
        $php .= 'namespace ' . $namespace . ';' . PHP_EOL;
        $php .= PHP_EOL;
    }
    $imports = [];
    $implements = [];
    $traitors = [];
    $excludesImports = [];
    $parentClass = $ref->getParentClass();
    $interfaces = $ref->getInterfaces();
    $traits = $ref->getTraits();
    $constants = $ref->getReflectionConstants();

    $parents = [];
    foreach ($interfaces as $interface) {
        $interfaceNames = $interface->getInterfaceNames();
        foreach ($interfaceNames as $name) {
            $parents[$name] = true;
        }
    }

    /** @noinspection DuplicatedCode */
    foreach ($interfaces as $interface) {
        $shortName = $interface->getShortName();
        $interfaceName = $interface->getName();
        if (isset($parents[$interfaceName])) {
            if (isset($imports[$interfaceName])) {
                $excludesImports[$interfaceName] = $shortName;
            }
            continue;
        }

        if (isset($imports[$interfaceName])) {
            $implements[$imports[$interfaceName]] = $interfaceName;
            continue;
        }
        if (isset($implements[$shortName])
            || $shortName === $ref->getShortName()
        ) {
            $shortName = "\\{$interface->getName()}";
        }
        if ($interface->getNamespaceName() === $ref->getNamespaceName()) {
            $excludesImports[$interfaceName] = true;
        }
        $imports[$interfaceName] = $shortName;
        $implements[$shortName] = $interfaceName;
    }
    /** @noinspection DuplicatedCode */
    foreach ($traits as $trait) {
        $shortName = $trait->getShortName();
        $traitName = $trait->getName();

        if ($trait->getNamespaceName() === $ref->getNamespaceName()) {
            $excludesImports[$traitName] = true;
        }

        if (isset($imports[$traitName])) {
            $traitors[$imports[$traitName]] = $traitName;
            continue;
        }
        if (isset($traitors[$shortName]) || $shortName === $ref->getShortName()) {
            $shortName = "\\{$trait->getName()}";
        }
        $imports[$traitName] = $shortName;
        $traitors[$shortName] = $traitName;
    }

    $parentAlias = null;
    if ($parentClass) {
        $parentClassName = $parentClass->getName();
        $parentShortName = $parentClass->getShortName();
        $parentNamespace = $parentClass->getNamespaceName();
        if (isset($imports[$parentClassName])) {
            $parentAlias = $imports[$parentClassName];
        } else {
            $parentAlias = $parentShortName;
            if ($parentNamespace === $namespace) {
                $excludesImports[$parentClassName] = true;
            } elseif ($parentShortName === $ref->getShortName()) {
                $parentAlias = "\\$parentClassName";
            } else {
                $imports[$parentClassName] = $parentShortName;
            }
        }
        foreach ($parentClass->getInterfaces() as $interface) {
            $interfaceName = $interface->getName();
            if (isset($implements[$interfaceName])) {
                unset($implements[$interfaceName]);
                if (isset($imports[$interfaceName])) {
                    $excludesImports[$interfaceName] = $interface->getShortName();
                }
            }
        }
    }

//    foreach ($implements as $shortName => $implement) {
//        $php .= 'use ' . $implement ;
//        if ($implement !== $shortName && (!str_contains($implement, '\\' ) || !str_ends_with($implement, '\\' . $shortName))) {
//            $php .= ' as ' . $shortName;
//        }
//        $php .= ';' . PHP_EOL;
//        $imports[$implement] = $shortName;
//    }
//
//    foreach ($traits as $trait) {
//        $php .= 'use ' . $trait->getName() . ';' . PHP_EOL;
//        $imports[$trait->getName()] = $trait->getShortName();
//    }
    $hasReturnType = false;
    $doc = $ref->getDocComment();
    $classText = '';
    if ($doc) {
        $classText .= $doc . PHP_EOL;
    }
    $isInterface = $ref->isInterface();
    if ($ref->isFinal()) {
        $classText .= 'final ';
    }
    if (!$isInterface && $ref->isAbstract()) {
        $classText .= 'abstract ';
    }
    if ($isInterface) {
        $classText .= 'interface ';
    } else if ($ref->isTrait()) {
        $classText .= 'trait ';
    } else {
        $classText .= 'class ';
    }

    $classText .= "{$ref->getShortName()}";
    if ($parentClass) {
        $parentAlias = $parentAlias ?: $parentClass->getShortName();
        $classText .= ' extends ' . $parentAlias;
    }

    $extended = '';
    if (!empty($implements) && !$isInterface) {
        $extended = 'implements ' . implode(', ', array_keys($implements));
    } elseif (!empty($implements) && $isInterface) {
        $extended = 'extends ' . implode(', ', array_keys($implements));
    }
    if (strlen($classText . $extended) >= 119) {
        $extended = explode(', ', $extended);
        $extended = implode(',' . PHP_EOL . '    ', $extended);
    }
    $classText .= ' ' . $extended;
    $classText .= PHP_EOL . '{' . PHP_EOL;
    if (!empty($trait)) {
        foreach ($traitors as $shortName => $traitor) {
            $classText .= '    use ' . $shortName . ';' . PHP_EOL;
        }
        $classText .= PHP_EOL;
    }

    $_4spaces = '    ';
    foreach ($constants as $value) {
        if ($value->getDeclaringClass()->getName() !== $className) {
            continue;
        }
        $doc = $value->getDocComment();
        if ($doc) {
            $classText .= $_4spaces . $doc . PHP_EOL;
        }
        $classText .= '    ' . implode(' ', Reflection::getModifierNames($value->getModifiers())) . ' const ' . $value->getName();
        $classText .= ' = ' . normalizeExport($value->getValue());
        $classText .= ';' . PHP_EOL . PHP_EOL;
    }

    $properties = $ref->getProperties();
    $obj = null;
    foreach ($properties as $property) {
        // check if property on current class
        if ($property->getDeclaringClass()->getName() !== $className) {
            continue;
        }

        $doc = $property->getDocComment();
        if ($doc) {
            $classText .= $_4spaces . $doc . PHP_EOL;
        }
        $classText .= '    ' . implode(' ', Reflection::getModifierNames($property->getModifiers())) . ' $' . $property->getName();
        $property->setAccessible(true);
        if ($property->isStatic()) {
            // static is nightmare
            $classText .= ($property->isDefault() ? ' = null' : ''); //normalizeExport($property->isDefault() ? $property->getValue($obj) : null);
        } else {
            if (!$obj && $ref->isInstantiable()) {
                $obj = $ref->newInstanceWithoutConstructor();
            }
            if ($obj) {
                $value = $property->getValue($obj);
                if (!is_scalar($value) && !is_null($value) && !is_array($value)) {
                    $classText .= ' = null';
                } else {
                    $classText .= $value === null ? '' : ' = ' . normalizeExport($value);
                }
            }
        }
        $classText .= ';' . PHP_EOL . PHP_EOL;
    }

    $methods = $ref->getMethods();
    foreach ($methods as $method) {
        // check if method on current class
        if ($method->getDeclaringClass()->getName() !== $className) {
            continue;
        }
        $doc = $method->getDocComment();
        if ($doc) {
            $doc = $_4spaces . $doc . PHP_EOL;
        }
        $methodStr = '';
        if ($isInterface) {
            $methodStr .= '    public function ' . $method->getName() . '(';
        } else {
            $methodStr .= '    ' . implode(' ', Reflection::getModifierNames($method->getModifiers())) . ' function ' . $method->getName() . '(';
        }
        $params = $method->getParameters();
        $paramStr = [];
        foreach ($params as $param) {
            $paramStrVal = '';
            $paramName = $param->getName();

            if ($param->hasType()) {
                $type = $param->getType();
                $typeName = $type->getName();
                if (!$type->isBuiltin()) {
                    if ($type->getName() === 'self' || $type->getName() === 'static') {
                        $type = $type->getName();
                    } elseif ($typeName === $ref->getName()) {
                        $type = $ref->getShortName();
                    } elseif (isset($imports[$typeName])) {
                        $type = $imports[$typeName];
                    } else {
                        try {
                            $ob = new \ReflectionClass($type->getName());
                            $shortName = $ob->getShortName();
                            if ($shortName === $ref->getShortName() || in_array($shortName, $imports)) {
                                $shortName = "\\{$type->getName()}";
                            } elseif ($ob->getNamespaceName() === $ref->getNamespaceName()) {
                                $excludesImports[$typeName] = true;
                            }
                        } catch (Throwable $e) {
                            $shortName = "\\{$type->getName()}";
                        }
                        $imports[$typeName] = $shortName;
                        $type = $shortName;
                    }
                }
                $paramStrVal = $type . ' ';
            }
            $_paramStr = '$' . $paramName;
            if ($param->isOptional() && !$param->isVariadic()) {
                if ($param->isDefaultValueConstant()) {
                    $constantName = $param->getDefaultValueConstantName();
                    if (str_contains($constantName, '\\')) {
                        if (str_starts_with($constantName, $ref->getNamespaceName())) {
                            $constantName = substr($constantName, strlen($ref->getNamespaceName()) + 1);
                        } elseif (!defined($constantName)) {
                            $constantName = substr($constantName, strrpos($constantName, '\\') + 1);
                        }
                    }
                    $paramStrVal .= $_paramStr . ' = ' . $constantName;
                } else {
                    try {
                        $paramStrVal .= $_paramStr . ' = ' . normalizeExport($param->getDefaultValue());
                    } catch (Throwable $e) {
                        $paramStrVal .= $_paramStr . ' = null';
                    }
                }
            } elseif ($param->isVariadic()) {
                $paramStrVal .= '...' . $_paramStr;
            } elseif ($param->isPassedByReference()) {
                $paramStrVal .= '&' . $_paramStr;
            } else {
                $paramStrVal .= $_paramStr;
            }
            $paramStr[] = $paramStrVal;
        }

        $methodStr .= implode(', ', $paramStr) . ')';
        if ($method->hasReturnType()) {
            $type = $method->getReturnType();
            $hasReturnType = true;
            $typeName = $type->getName();
            $lowerName = strtolower($typeName);
            if ($type->isBuiltin() || $lowerName === 'self' || $lowerName === 'static') {
                $methodStr .= ' : ' . $typeName;
            } elseif (isset($imports[$typeName])) {
                $methodStr .= ' : ' . $imports[$typeName];
            } else {
                try {
                    $ob = new \ReflectionClass($typeName);
                    $shortName = $ob->getShortName();
                    if ($shortName === $ref->getShortName()) {
                        $shortName = "\\{$type->getName()}";
                    } elseif ($ob->getNamespaceName() === $ref->getNamespaceName()) {
                        $excludesImports[$typeName] = true;
                    }
                } catch (Throwable $e) {
                    $shortName = "\\{$type->getName()}";
                }
                $imports[$typeName] = $shortName;
            }
        } else {
            $methodName = $method->getName();
            $lowerName = strtolower($methodName);
            if ($method->isStatic() && $lowerName === 'getinstance') {
                $doc = $doc ?: '    /**' . PHP_EOL
                    . '     * @noinspection PhpInconsistentReturnPointsInspection' . PHP_EOL
                    . '     */';
                $methodStr .= ' : self';
            } elseif (preg_match('~^has|can|should|is|must~i', $methodName)
                || preg_match('~Has|Can|Should|Is|Must~', $methodName)
            ) {
                $doc = $doc ?: '    /**' . PHP_EOL
                    . '     * @noinspection PhpInconsistentReturnPointsInspection' . PHP_EOL
                    . '     */';
                $methodStr .= ' : bool';
            } elseif (preg_match('~count|length|total|size~i', $methodName)) {
                $doc = $doc ?: '    /**' . PHP_EOL
                    . '     * @noinspection PhpInconsistentReturnPointsInspection' . PHP_EOL
                    . '     */';
                $methodStr .= ' : int';
            } elseif (str_starts_with($methodName, 'get')) {
                $doc = '    /**' . PHP_EOL . '     * @return mixed' . PHP_EOL
                    . '     * @noinspection PhpReturnDocTypeMismatchInspection' . PHP_EOL
                    . '     * @noinspection PhpInconsistentReturnPointsInspection' . PHP_EOL
                    . '     */';
            }
        }

        if (strlen($methodStr) > 118) {
            $methodStr = explode(', ', $methodStr);
            $methodStr = implode(',' . PHP_EOL . '    ', $methodStr);
        }

        $classText .= $doc ? $doc . PHP_EOL : '';
        $classText .= $methodStr;
        if (!$isInterface) {
            $classText .= PHP_EOL . '    {' . PHP_EOL;
            $classText .= '        // TODO: Implement ' . $method->getName() . '() method' . PHP_EOL;
            $classText .= '    }' . PHP_EOL . PHP_EOL;
        } else {
            $classText .= ';' . PHP_EOL . PHP_EOL;
        }
    }

    $classText = trim($classText) . PHP_EOL;
    $classText .= '}' . PHP_EOL;

    // sort imports by keys and keep key
    ksort($imports);
    foreach ($imports as $class => $import) {
        if (isset($excludesImports[$class]) || str_starts_with($import, '\\')) {
            continue;
        }
        $php .= 'use ' . $class . ';' . PHP_EOL;
    }

    $version = \WHMCS\Application::FILES_VERSION;
    $additionalComment = $hasReturnType ? PHP_EOL . ' *' . PHP_EOL . ' * @noinspection PhpInconsistentReturnPointsInspection' : '';
    $time = date('c');
    $spec = PHP_VERSION . ' (' . PHP_OS . ')';
    echoVeryVerbose(
        "Generating Stub for {$ref->getName()} ({$ref->getFileName()})" . PHP_EOL
    );
    return '<?php'
        . PHP_EOL
        . <<<PHP
/**
 * WHMCS STUB (vendor/whmcs/whmcs-foundation/lib/{$ref->getShortName()}.php)
 *
 * WHMCS Version: {$version}
 * Generated Time: {$time}
 * Php: {$spec}{$additionalComment}
 */
declare(strict_types=1);

PHP

        . PHP_EOL
        . trim($php) . PHP_EOL . PHP_EOL
        . trim($classText) . PHP_EOL;
}

if (!$isForce && file_exists($stubDIr)) {
    // ask interactive answer
    $stop = true;
    if (!$isQuiet) {
        $answer = null;
        do {
            echoing("\033[31mStub Directory is already exists!\033[0m\n");
            echoing("\033[33m -> $stubDIr \033[0m\n");
            echoing("\033[32mDo you want to delete it? [y(es)/N(o)] \033[0m");
            $answer = strtolower(trim(fgets(STDIN)));
            $answer = $answer === '' ? 'n' : $answer;
            if ($answer === 'y' || $answer === 'yes') {
                deleteStub();
                $stop = false;
                break;
            }
        } while (!in_array($answer, ['y', 'yes', 'n', 'no']));
    }
    if ($stop) {
        // show cancelled
        echoing("\033[31mStub Generation Cancelled\033[0m\n");
        exit(100);
    }
}

echoing("\033[32mLoading WHMCS Files\033[0m\n");

/**
 * @var SplFileInfo $file
 */
foreach ($recursive as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getRealPath();
    $base = substr($path, strlen($basePath) + 1);
    $dirname = dirname($base);
    if ($dirname === '.') {
        $dirNamespace = $nameSpace;
    } else {
        $dirNamespace = $nameSpace . '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $dirname);
    }
    $baseName = $file->getBasename('.php');
    $className = $dirNamespace . '\\' . $baseName;
    $whmcsFilesTarget[$className] = $stubDIr . $baseDirectory . DIRECTORY_SEPARATOR . $base;
    $whmcsFilesSource[$className] = $path;
}

$count = count($whmcsFilesTarget);
if ($count === 0) {
    echoing("\033[31mError: No WHMCS Files Found\033[0m\n");
    exit(100);
}

if (!is_dir($stubDIr)) {
    mkdir($stubDIr, 0777, true);
}
if (!is_dir($stubDIr)) {
    echo "\033[31mError: stub-dir is not a directory\033[0m\n";
    exit;
}
if (!is_writable($stubDIr)) {
    echo "\033[31mError: stub-dir is not writable\033[0m\n";
    exit;
}
echoing("\033[32mFound {$count} WHMCS Files\033[0m\n");
echoing("\033[32mGenerating WHMCS Stubs\033[0m\n");
foreach ($whmcsFilesTarget as $className => $file) {
    try {
        $stub = genStubFromClassName($className);
        if (!$stub) {
            echoVerbose("\033[33mSkip: {$className}\033[0m\n");
            continue;
        }
        if (!is_dir(dirname($file))) {
            echoVerboseVerbose("\033[33mCreating Directory: {$file}\033[0m\n");
            mkdir(dirname($file), 0777, true);
        }
        if (!is_writable(dirname($file))) {
            echo "\033[31mError: {$file} is not writable\033[0m\n";
            deleteStub();
            echo "\033[31mError: Stub Generation Failed\033[0m\n";
            exit(255);
        }
        echoVerbose("\033[32mGenerating: {$className}\033[0m\n");
        echoVerboseVerbose("\033[32mGenerating: {$file}\033[0m\n");
        file_put_contents($file, $stub);
    } catch (Throwable $e) {
        echo "\033[31mError: {$e->getMessage()}\033[0m\n";
        deleteStub();
        exit(255);
    }
}

unset($whmcsFilesTarget);
$phpcbf = dirname(__DIR__) . '/vendor/bin/phpcbf';
if (file_exists($phpcbf)) {
    echoing("\033[32mRunning PHPCBF using binary\033[0m\n");

    $cmd = is_executable($phpcbf) ? $phpcbf : PHP_BINARY . ' ' . $phpcbf;
    array_shift($argv);
    $cmd = $cmd . ' ' . implode(' ', $argv);
    $cmd = escapeshellcmd($cmd);

    // listening to live output
    if (function_exists('proc_open') && function_exists('proc_close')) {
        $proc = proc_open($cmd, [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ], $pipes);
        if (is_resource($proc)) {
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);
            while (!feof($pipes[1]) || !feof($pipes[2])) {
                $read = [$pipes[1], $pipes[2]];
                $write = null;
                $except = null;
                $changed = stream_select($read, $write, $except, 0, 1000);
                if ($changed === false) {
                    break;
                }
                foreach ($read as $stream) {
                    $line = fgets($stream);
                    if ($line) {
                        echo $line;
                    }
                }
            }
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        }
    } elseif (function_exists('popen')) {
        $cmd = $cmd . ' 2>&1';
        $pipes = [];
        $proc = popen($cmd, 'r');
        // execute
        while (!feof($proc)) {
            $line = fgets($proc);
            if ($line) {
                echo $line;
            }
        }
        pclose($proc);
    } else {
        $pipes = [];
        $proc = null;
        $output = shell_exec($cmd);
        if ($output) {
            echo $output;
        }
    }
    exit;
}

echoing("\033[32mRunning PHPCBF using PHP\033[0m\n");
$_SERVER['argv'] = $argv;
$exitCode = $runner->runPHPCBF();
exit($exitCode);
