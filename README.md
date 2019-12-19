# restm
Каркас для сборки клиента некого REST API сервера. Возможно пригодится тем, кто пишет межсерверное взаимодействие не используя различные SOAP монстры и прочие RPC.

## Зачем это и как это работает

Чтобы избежать хардкода и магических строк в работе с REST данными в случаях, 
когда сервер кроме своих обычных функций выступает еще и клиентом другого REST сервера. 
Т.е. для тех случаев, когда нужны strict-модели для REST данных. 

## Что значит strict-модель? 

Все просто - набор свойств модели должен соответствовать переданным данным. 
Если необходимые данные отсутствуют (изменились, новая версия и т.д.) - 
дальнейшая их обработка невозможна. Вот и все. Дело конкретно в php осложняется тем, 
что дефакто принято работать с ассоциативными массивами, когда речь заходит о JSON и REST -
 это удобно, пока структуры данных просты и их описание можно держать в голове. Можно обойтись 
 константами, чтобы описывать ключи и вести документацию в комментариях, 
 но когда их под сотню - это не решение проблемы. 

## Model
Модель представляет из себя класс, наследуемый от `\icework\restm\base\Model`.
Типичный пример реализации:

```json
{"id":  1, "name": "john", "surname": "doe"}
```

```php
use \icework\restm\base\Model;

class MUser extends Model
{
  public $id;
  public $surname;
  public $name;

  protected function attachments(): array {
    return []; // no dependencies
  }
}
```
В первую очередь, модель загрузит в себя все значения json полей, 
имена которых соответствуют публичным свойствам класса. 
Если хотя бы одно свойство модели не будет найдено в json, 
будет брошено исключение с описанием проблемы.

Затем, если с полями все в порядке, модель реализует функционал вложенных моделей. 
Все вложения объявляются в методе `attachments`. Это будет полезно, если сервер отдает следующую структуру:

```json
[{
  "id": 1,
  "username": "john",
  "mather": {
    "id": 21,
    "username": "jessy"
  }  
}, {
  "id": 2,
  "username": "doe",
  "mather": {
    "id": 22,
    "username": "riot"
  }  
}]
```
То неплохо бы иметь следующие описание в виде strict-моделей и методы для их безопасной загрузки:
```json
[{
  "id": 1,
  "username": "john",
  "mather": {
    "id": 21,
    "username": "jessy"
}  
}, {
  "id": 2,
  "username": "doe",
  "mather": {
    "id": 22,
    "username": "riot"
  }  
}]
```
```php
use \icework\restm\base\Model;

class MUser extends Model {
  public $id;
  public $username;
  
  protected function attachments() : array {
    return [
      // don't user recursion after load this item 
      'mather' => MUser::asOneDeclaration(false)
      // ... other attachments
    ]; 
  }
}

$data=json_decode("...", true);
$models=Model::build(MUser::asManyDeclaration(), $data);
```

## HttpConnector

Позволяет удобно собрать запрос, беря на себя рутину установки перехватчиков ответа 
(исключения, наследуемые от HttpInterceptException), 
моделей ввода (любой класс, который осуществляет метод `getInputData()` интерфейса 
`InputModel`) и моделей ответа (класс, наследуемый от `Model`). 

Типичный боевой пример: 

```php
use \icework\restm\interfaces\InputModel;
use \icework\restm\base\Model;
use \icework\restm\HttpInterceptException;
use icework\restm\HttpConnector;

class AuthInputModel implements InputModel {
  public $username;
  public $password;
  public function __construct($username, $password) {
    $this->username=$username;
    $this->password=$password;
  }
  public function getInputData() : array {
    return [
      'username' => $this->username, 
      'password' => $this->password
    ];
  }
}

// for 200 code
class JsonAuth extends Model {
  public $id;
  public $surname;
  protected function attachments(): array {
    return []; // no dependencies
  }
}

// for 400 code
class EValidationItem extends Model {
  public $field;
  public $message;
  protected function attachments(): array {
    return []; // no dependencies
  }
}

// 
class InvalidInterceptException extends HttpInterceptException
{
  /**
   * @var EValidationItem[]
   */
  private $__data;
  public function populate($data): void {
    $this->__data=$data;
  }
  public function getData() : array {
    return $this->__data;
  }
}

$connector=(new HttpConnector())
      ->open("http://www.example.com")
      ->addHeaders([
        HttpConnector::ACCEPT_HEADER => HttpConnector::JSON_CONTENT_TYPE,
        HttpConnector::CONTENT_TYPE_HEADER => HttpConnector::JSON_CONTENT_TYPE
      ])
      ->method("user/auth")
      ->setInputModel(new AuthInputModel("example@mail.foo", "123456"))
      ->addException(
        HttpConnector::HTTP_BAD_REQUEST, // intercept 400 code
        new InvalidInterceptException(EValidationItem::asManyDeclaration()) // many items
      )
      ->asPostRequest();
      
try {
  $mAuth=$connector->run(JsonAuth::asOneDeclaration());
  // do something with the model
} catch(InvalidInterceptException $interceptor) {
  $validationErrors=$interceptor->getData();
  // do something with the errors
} catch(Exception $e) {
  // something wrong
  throw $e;
}
  
```

Как видно из примера кода, в качестве перехватчиков выступают исключения - их удобно 
отлавливать блоками catch. Перехватчик вешается на определенный httpCode и 
позволяет собрать иную модель ответа - это полезно для валидации и прочего boilerplate.

Коннектором удобно пользоваться в синглтоне, например компоненте Yii2:

```php
use yii\base\Component;
use \icework\restm\HttpConnector;
use icework\restm\interfaces\InputModel;

class Client extends Component
{
  const METHOD_AUTH='user/auth';

  // configurable property
  public string $baseUrl;
  
  protected function createConnector() : \icework\restm\HttpConnector {
    return (new HttpConnector())
      ->open($this->baseUrl)
      ->addHeaders([
        HttpConnector::ACCEPT_HEADER => HttpConnector::JSON_CONTENT_TYPE,
        HttpConnector::CONTENT_TYPE_HEADER => HttpConnector::JSON_CONTENT_TYPE
      ]);
  }
  public function auth(AuthInputModel $model) : JsonAuth {
    return $this->createConnector()
      ->method(self::METHOD_AUTH)
      ->setInputModel($model)
      ->addException(
        HttpConnector::HTTP_BAD_REQUEST,
        new InvalidInterceptException(EValidationItem::asMany())
      )
      ->asPostRequest()
      ->run(JsonAuth::asOneDeclaration());
  }
}
```
