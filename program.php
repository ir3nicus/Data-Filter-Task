<?php declare(strict_types=1);

final class DataProvider
{
    public function getData(string $filePath): array
    {
        return json_decode(file_get_contents($filePath), true);
    }

    public function getRulesNormalizedData(string $filePath): array
    {
        $data = $this->getData($filePath);
        return [
            'found' => $this->getNormalizedData($data['findProducts']),
            'matched' => $this->getNormalizedData($data['matchProducts'])
        ];
    }

    private function getNormalizedData(array $data)
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[$value['parameter']] = $value['equals'];
        }
        return $normalized;
    }
}
final class RuleValidator
{
    private $findRules;
    public $matchRules;

    public function __construct(array $findRules, array $matchRules)
    {
        $this->findRules = $findRules;
        $this->matchRules = $matchRules;
    }

    public function isValid(array $product): bool
    {
        foreach ($this->findRules as $name => $ruleValue) {
            if ($ruleValue == 'any' && !isset($product['parameters'][$name])) {
                return false;
            }
            if ($ruleValue == 'is_empty' && isset($product['parameters'][$name])) {
                return false;
            }
            if (!in_array($ruleValue, ['any', 'is_empty']) && isset($product['parameters'][$name]) && ($product['parameters'][$name] != $ruleValue)) {
                return false;
            }
        }
        return true;
    }

    public function getMatchedProducts(array $parentProduct, array $productsArray): array
    {
        $matched = [];
        foreach ($productsArray as $key => $product) {
            $isWrong = false;
            if ($product['id'] == $parentProduct['id']) continue;
            foreach ($this->matchRules as $name => $ruleValue) {
                if ($ruleValue == 'this' && isset($product['parameters'][$name]) && $parentProduct['parameters'][$name] != $product['parameters'][$name]) {
                    $isWrong = true;
                    break 1;
                }
            }
            if (!$isWrong) {
                $matched[] = $product['symbol'];
            }
        }
        return $matched;
    }
}

if (!(isset($_SERVER['argv'][1]))) die('brak argumentu 1');
if (!(isset($_SERVER['argv'][2]))) die('brak argumentu 2');

$start = microtime(true);
$result = [];
$dataProvider = new DataProvider();
$allProducts = $dataProvider->getData($_SERVER['argv'][1]);
$rulesData = $dataProvider->getRulesNormalizedData($_SERVER['argv'][2]);
$ruleValidator = new RuleValidator($rulesData['found'], $rulesData['matched']);
$matchedProducts = array_filter($allProducts, function ($arr) use ($rulesData) {
    $isOk = true;
    foreach ($rulesData['matched'] as $key => $rule) {
        if ($rule != 'this') {
            if ($rule == 'any' && !isset($arr['parameters'][$key])) {
                $isOk = false;
                continue;
            } else if ($rule == 'is_empty' && isset($arr['parameters'][$key])) {
                $isOk = false;
                continue;
            } else if (!isset($arr['parameters'][$key]) || ($arr['parameters'][$key] != $rule)) {
                $isOk = false;
                continue;
            }
        }
    }
    return $isOk;
});
$foundProducts = array_filter($allProducts, function ($product) use ($ruleValidator) {
    return $ruleValidator->isValid($product);
});
$matchedProducts = array_values($matchedProducts);
$paramsMatchedProducts = [];

foreach ($matchedProducts as $matchedProduct) {
    foreach ($ruleValidator->matchRules as $key => $matchRule) {
        if ($matchRule == 'this') {
            if (!isset($matchedProduct['parameters'][$key])) continue;
            $paramsMatchedProducts[$key][$matchedProduct['parameters'][$key]][] = $matchedProduct['symbol'];
        }
    }
}

$result = [];
$count = count($paramsMatchedProducts);
foreach ($foundProducts as $key => $product) {
    $extraMatched = [];
    foreach ($paramsMatchedProducts as $key => $match) {
        $extraMatched[$key] = array_values($match[$product['parameters'][$key]] ?? []);
    }
    if ($count == 1) {
        $result[$product['symbol']] = $extraMatched[$key] ?? [];
    } else {
        $result[$product['symbol']] = array_values(call_user_func_array('array_intersect', $extraMatched));
    }
}

//echo microtime(true) - $start; die;
echo json_encode($result, JSON_PRETTY_PRINT);
//echo microtime(true) - $start;