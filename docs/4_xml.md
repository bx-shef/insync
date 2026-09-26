# Парсинг XML
> Разбор **XML** сделан через [SbWereWolf/xml-navigator](https://github.com/SbWereWolf/xml-navigator).
> 
> Статья на [Хабре](https://habr.com/ru/post/712106/).

* `\Shef\InSync\TraitList\Xml\ToArray` - трейт для парсинга в массив
* `\Shef\InSync\TraitList\Xml\ToYield` - трейт для парсинга через yield (предпочительней для средних и выше объемов)

> Большие xml файлы парсить не проблема. Нужно интервал разбора отрегулировать под объем данных, что бы агент успел отработать. 
> 
> В модуле **[shef.demosync](https://marketplace.1c-bitrix.ru/solutions/shef.demosync/)** на основе импорта **ДК в смарт процесс** можно протестировать работу импорта файлов с размером 10Mb.


[← API](docs/3_api.md) | [↑ Содержание](README.md) | [Компоненты →](docs/5_components.md)