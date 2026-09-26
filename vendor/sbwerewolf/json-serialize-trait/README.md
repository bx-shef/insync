# JsonSerialize

Library with trait of ability to output all properties
of class-exemplar at call json_encode()

## How to install
`composer require sbwerewolf/json-serialize-trait`

## How to use

To use JsonSerialize you should declare use statement into class

```php
use \SbWereWolf\JsonSerializable\JsonSerializeTrait;
```

Example

```php
class TraitTest implements JsonSerializable
{
/* Declaration of trait use */
    use \SbWereWolf\JsonSerializable\JsonSerializeTrait;
/* Declaration of class properties */
    private $privateProp = 'privateProp';
    protected $protectedProp = 'protected property value';
    public $publicProp = 'public class member';
}
/* Convert class-exemplar to JSON */
echo json_encode(new TraitTest(),JSON_PRETTY_PRINT);
/*
{
    "privateProp": "privateProp",
    "protectedProp": "protected property value",
    "publicProp": "public class member"
}
*/
```

## Contacts

```
Volkhin Nikolay
e-mail ulfnew@gmail.com
phone +7-902-272-65-35
Telegram @sbwerewolf
```

Chat with me via messenger

- [Telegram chat with me](https://t.me/SbWereWolf)
- [WhatsApp chat with me](https://wa.me/79022726535) 