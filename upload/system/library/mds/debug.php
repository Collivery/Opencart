<?php

/**
 * @author Peter West, https://github.com/peterjwest
 * @see https://gist.github.com/peterjwest/5459304
 * @since 1.1.0
 *
 * @param $var
 * @param int $maxDepth
 * @param array $referenceMap
 * @param int $depth
 *
 * @return string|null
 */
// Returns debug info for a variable
function debug($var, $maxDepth = 6, &$referenceMap = [], $depth = 1) {
    if (is_array($var)) {
        return inspect_contents($var, $maxDepth, $referenceMap, $depth);
    }
    if (is_object($var)) {
        return inspect_object($var, $maxDepth, $referenceMap, $depth);
    }
    if (is_resource($var)) {
        return 'Resource#('.get_resource_type($var).')';
    }
    return var_export($var, true);
}

// Returns debug info for an object,
// $referenceMap prevents repeated objects from being listed recursively
function inspect_object($obj, $maxDepth = 6, &$referenceMap = [], $depth = 0) {
    $class = get_class($obj);
    $hash = spl_object_hash($obj);
    $contents = '';

    // We create a sub array for each class
    if (!isset($referenceMap[$class])) {
        $referenceMap[$class] = [];
    }

    // We use the object hash to track each instance of the class
    if (!isset($referenceMap[$class][$hash])) {
        $referenceMap[$class][$hash] = count($referenceMap[$class]);
        $contents = ' ' . inspect_contents($obj, $maxDepth, $referenceMap, $depth);
    }
    return '' . $class . "#". $referenceMap[$class][$hash] . $contents;
}

// Returns debug info for the contents of an object or array
function inspect_contents($var, $maxDepth = 6, &$referenceMap = [], $depth = 0) {
    if ($maxDepth !== null && $depth >= $maxDepth) {
        $contents = str_repeat('  ', $depth + 1) . '...';
    }
    else {
        $values = [];
        // We show the keys to each value if its not a numeric array
        $showKeys = is_object($var) || is_associative($var);
        foreach ((array) $var as $key => $val) {
            // Addresses PHP's strange behavior for private/protected properties when array casting objs:
            if (is_object($var)) {
                $key = preg_replace('~^\0[^\0]*\0~', '*', $key);
            }

            // Quote the key if it has spaces
            if (strpos($key, ' ') !== false) {
                $key = "'$key'";
            }

            // Forms the output of each value
            $key = $showKeys ? "$key: " : '';
            $indent = str_repeat('  ', $depth + 1);
            $values[] = $indent . $key . debug($val, $maxDepth, $referenceMap, $depth + 1);
        }
        $contents = implode(",\n", $values);
    }

    // Delimiters are selected by the variable type
    $delimiters = ['[', ']'];
    if (is_object($var)) {
        $delimiters = ['(', ')'];
    }
    else if (is_associative($var)) {
        $delimiters = ['{', '}'];
    }

    return $delimiters[0] . "\n" . $contents . "\n" . str_repeat('  ', $depth) . $delimiters[1];
}

// Returns if an array has numeric keys only
function is_associative($array) {
    return array_keys($array) !== range(0, count($array) - 1);
}
