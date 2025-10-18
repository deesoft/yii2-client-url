<?php

namespace dee\clientUrl;

use Yii;
use yii\web\View;
use ReflectionClass;
use yii\helpers\Url;
use yii\web\UrlRule;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\UrlManager;
use yii\web\JsExpression;
use yii\web\CompositeUrlRule;

/**
 * Description of Helper
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Helper
{

    /**
     * @param UrlManager|null $manager
     * @return array
     */
    public static function getUrlRules($manager = null)
    {
        if (!$manager) {
            $manager = Yii::$app->urlManager;
        }
        $result = [];
        foreach ($manager->rules as $rule) {
            if ($rule instanceof UrlRule) {
                $result[] = self::getRuleInfo($rule);
            } elseif ($rule instanceof CompositeUrlRule) {
                self::getRuleRecursive($rule, $result);
            }
        }
        return $result;
    }

    /**
     *
     * @param UrlRule $rule
     * @return array
     */
    protected static function getRuleInfo($rule)
    {
        $ref = new ReflectionClass($rule);
        $props = ['placeholders', '_template', '_routeRule', '_paramRules', '_routeParams'];
        $row = [
            //'pattern' => $rule->pattern,
            'route' => $rule->route,
            'verb' => $rule->verb,
            'suffix' => $rule->suffix,
            'encodeParams' => $rule->encodeParams,
            'host' => $rule->host,
            'defaults' => $rule->defaults,
        ];
        foreach ($props as $name) {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $row[ltrim($name, '_')] = $prop->getValue($rule);
        }
        if ($row['routeRule']) {
            $regex = Html::escapeJsRegularExpression($row['routeRule']);
            $row['routeRule'] = new JsExpression(str_replace('?P<', '?<', $regex));
        }
        if ($row['paramRules']) {
            foreach ($row['paramRules'] as $key => $value) {
                if ($value) {
                    $row['paramRules'][$key] = new JsExpression(Html::escapeJsRegularExpression($value));
                }
            }
        }

        foreach (['paramRules', 'defaults', 'routeParams', 'placeholders'] as $prop) {
            if (empty($row[$prop])) {
                $row[$prop] = (object) [];
            }
        }
        return $row;
    }

    /**
     *
     * @param CompositeUrlRule $rule
     * @param array $result
     */
    protected static function getRuleRecursive($rule, &$result)
    {
        $ref = new ReflectionClass($rule);
        $prop = $ref->getProperty('rules');
        $prop->setAccessible(true);
        $rules = $prop->getValue($rule);
        foreach ($rules as $child) {
            if ($child instanceof UrlRule) {
                $result[] = self::getRuleInfo($rule);
            } elseif ($child instanceof CompositeUrlRule) {
                self::getRuleRecursive($child, $result);
            }
        }
    }

    /**
     * @param View $view
     * @param string $name
     * @param UrlManager|null $manager
     */
    public static function registerJs($view, $name = 'yiiUrl', $manager = null)
    {
        list(, $urlAsset) = $view->assetManager->publish(__DIR__ . '/assets/url.js');
        $view->registerJsFile($urlAsset, ['position' => View::POS_HEAD]);

        if (!$manager) {
            $manager = Yii::$app->urlManager;
        }
        $js = sprintf('var %s = initYiiUrl(%s);', $name, Json::htmlEncode([
            'suffix' => $manager->suffix,
            'baseUrl' => $manager->showScriptName ? $manager->getScriptUrl() : $manager->getBaseUrl(),
            'rules' => static::getUrlRules($manager),
            'home' => Url::home(),
            'base' => $manager->getBaseUrl(),
        ]));
        $view->registerJs($js, View::POS_HEAD);
    }
}
