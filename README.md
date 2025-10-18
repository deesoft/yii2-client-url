Yii2 Client Side Url Manager
===================
This is the Yii 2 client-side url manager.


Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist deesoft/yii2-client-url "*"
```

or add

```
"deesoft/yii2-client-url": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it at main layout  :

```php
dee\clientUrl\Helper::registerJs($this);
```

Create Url
----------
Use function ```yiiUrl``` to generate url from route. It's equivalent with ```yii\helpers\Url::to()```.
```js
const {yiiUrl} = window;

const url = yiiUrl('product/view', {id: row.id}); // equivalent Url::to(['/product/view', id' => $row->id])
```