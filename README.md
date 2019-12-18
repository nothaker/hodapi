# restm
Каркас для сборки клиента некого REST API сервера. Возможно пригодится тем, кто пишет межсерверное взаимодействие не используя различные SOAP монстры и прочие RPC.

## Зачем это и как это работает

Чтобы избежать хардкода и магических строк в работе с REST данными в случаях, когда сервер кроме своих обычных функций выступает еще и клиентом другого REST сервера. Т.е. для тех случаев, когда нужны strict модели REST данных.

Модель представляет из себя класс, наследуемый от `BaseOutputModel`. Типичный пример:

```php
class MAuth extends BaseOutputModel
{
  public int $id;
  public string $surname;
  public string $name;
  public string $role;

  protected function dependencies(): array
  {
    return []; // no dependencies
  }
}
```
Метод `dependencies` позволяет описать зависимости в конечной модели, проще говоря вложенные данные на основе зависимых моделей. Например:

```php
class MMother extends BaseOutputModel {
  public $id;
  public $name;
  protected function dependencies(): array {
    return []; // no dependencies
  }
}

class MСoursework extends BaseOutputModel {
  public $id;
  public $content;
  protected function dependencies(): array {
    return []; // no dependencies
  }
}

class MStudent extends BaseOutputModel
{
  public int $id;
  public string $surname;
  public MMother $mother;
  public array $courseworks;

  protected function dependencies(): array {
    return [
      'mother' => MMather::asOne(),
      'courseworks' => MCoursework::asMany()
    ]; 
  }
}
```
## HttpConnector

Позволяет удобно собрать запрос, беря на себя рутину установки перехватчиков ответа (исключения, наследуемые от HttpInterceptException), моделей ввода (любой класс, который осуществляет метод `getInputData()` интерфейса `InputModelInterface`) и моделей ответа (класс, наследуемый от `BaseOutputModel`). Типичный боевой пример: 

HttpConnector позволяет "собрать" запрос, используя "лестничный" код. Для примера:

```php
class InputModel implements InputModelInterface {
  public $username;
  public $password;
  public function __construct($username, $password) {
    $this->username=$username;
    $this->password=$password;
  }
  public function getInputData() {
    return ['username' => $username, 'password' => $password];
  }
}

// for 200 code
class MAuth extends BaseOutputModel {
  public $id;
  public $surname;
  protected function dependencies(): array {
    return []; // no dependencies
  }
}

// for 400 code
class EValidationItem extends BaseOutputModel {
  public $attribute;
  public $message;
  protected function dependencies(): array {
    return []; // no dependencies
  }
}

// 
class InvalidInputInterceptor extends HttpInterceptException
{
  /**
   * @var EValidationItem[]
   */
  private array $__data;
  public function populate($data): void {
    $this->__data=$data;
  }
  public function getData() : array {
    return $this->__data;
  }
}

$connector=(new HttpConnector())
      ->open("http://1.2.3.4:8080")
      ->addHeaders([
        HttpConnector::ACCEPT_HEADER => HttpConnector::JSON_CONTENT_TYPE,
        HttpConnector::CONTENT_TYPE_HEADER => HttpConnector::JSON_CONTENT_TYPE
      ])
      ->method("user/auth")
      ->setInputModel(new InputModel("example@mail.foo", "123456"))
      ->addInterceptor(
        HttpConnector::HTTP_BAD_REQUEST, // intercept 400 code
        new InvalidInputInterceptor(EValidationItem::asMany()) // many items
      )
      ->asPostRequest();
      
try {
  $mAuth=$connector->run(MAuth::asOne());
  // do something with the model
} catch(InvalidInputInterceptor $interceptor) {
  $validationErrors=$interceptor->getData();
  // do something with the errors
} catch(Exception $e) {
  // something wrong
  throw $e;
}
  
```

Коннектором удобно пользоваться в синглтоне компонента Yii2:

```php
class Client extends Component
{
  const METHOD_AUTH='user/auth';

  // configurable property
  public string $baseUrl;
  
  protected function createConnector() : HttpConnector {
    return (new HttpConnector())
      ->open($this->baseUrl)
      ->addHeaders([
        HttpConnector::ACCEPT_HEADER => HttpConnector::JSON_CONTENT_TYPE,
        HttpConnector::CONTENT_TYPE_HEADER => HttpConnector::JSON_CONTENT_TYPE
      ]);
  }
  public function auth(InputModelInterface $model) : MAuth {
    return $this->createConnector()
      ->method(self::METHOD_AUTH)
      ->setInputModel($model)
      ->addInterceptor(
        HttpConnector::HTTP_BAD_REQUEST,
        new InvalidInputInterceptor(EValidation::asMany())
      )
      ->asPostRequest();
  }
}
```
