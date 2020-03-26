<?php

namespace App\V3\Services\Queue\Callback;

use App\V3\Console\Jobs\Core\JobException;
use App\V3\Services\Queue\FailedQueueWork;
use App\V3\Services\Queue\QueueException;
use App\V3\Services\Queue\RabbitPushAgainException;
use App\V3\Services\Rabbit\RabbitIncorrectDataException;
use App\V3\Services\Rabbit\RabbitJobMessage;
use App\V3\Services\Rabbit\RabbitLogger;

class RabbitCallback {

    protected static $pause = 100;

    /**
     * @param RabbitLogger $rabbitLogger
     * @return \Closure
     */
    public static function getCallback(RabbitLogger $rabbitLogger) {
        return function($message) use ($rabbitLogger) {
            $rabbitLogger->setMessage($message);
            //не уверен что необходимо, но лишним не будет
            usleep(static::$pause);

            try {
                $rabbitMessage = new RabbitJobMessage($message);
                //получаем класс исполнителя
                $executor = $rabbitMessage->getExecutor();
                //!WARNING! А ты, зарегистрировал свою работу?!

                //если время выполнения ещё не наступило
                //@todo: вот здесь https://habr.com/post/235983/ есть ссылка на
                //https://github.com/rabbitmq/rabbitmq-delayed-message-exchange
                //ещё вот много интересного понаписано http://jstructure.com/post/re-execution-of-tasks-with-different-repetition-waiting-times-in-rabbitmq/
                if (!$rabbitMessage->isAdventureTime()) {
                    //то не выполняем данную задачу, а снова шлём в очередь
                    throw new RabbitPushAgainException($rabbitMessage, $executor, 'The Adventure time has not come');
                }

                //Если не пришло подтверждения о выполнении работы, то значит работа не выполнена
                if (!$executor->handle($rabbitMessage->getParams())) {
                    throw new QueueException('Work not return success signal');
                }
                $rabbitMessage->confirmDelivery();
            }
            catch (RabbitIncorrectDataException $exception) {
                //логируем несуразность, и забываем о ней.
                $rabbitLogger->logWarning($exception->getMessage());
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            }
            //Повторная подача объявления. Пока время выполнения не настало
            catch (RabbitPushAgainException $rabbitPushAgainException) {
                $rabbitMessage = $rabbitPushAgainException->getRabbitMessage();
                $rabbitMessage->pushAgain($rabbitPushAgainException->getExecutor());
                $rabbitMessage->confirmDelivery();
            }
            //!!WARNING!! Важно, чтобы не перехватывался QueueRestartSignal. А он наследуется от QueueExceptions
            catch(JobException $exception) {
                //логируем и забываем
                $rabbitLogger->logWarning($exception->getMessage());
                $rabbitMessage->confirmDelivery();
            }
            //всё остальное ошибка работы
            catch(\Throwable $throwable) {
                //логируем и отправляем снова в глобальную очередь. если более 3х попыток, забываем.
                $rabbitMessage->confirmDelivery();
                if (empty($executor)) {
                    $rabbitLogger->logError('Empty executor : ' . $throwable->getMessage());
                }
                elseif ($rabbitMessage->isLimitAttempts()) {
                    $rabbitLogger->logError('Limit max tries. Error : ' . $throwable->getMessage());
                }
                else {
                    $rabbitLogger->logError($throwable->getMessage());
                    $rabbitMessage->pushNewAttempts($executor);
                    // я сейчас убил процес. и всё начало логироваться в БД. как я понимаю, причина именно в потере конекта с БД,
                    // которую воркер залогировал, и продолжил работу. Нужно его в таких ситуациях перезагружать
                    throw new FailedQueueWork($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            }
        };
    }

}