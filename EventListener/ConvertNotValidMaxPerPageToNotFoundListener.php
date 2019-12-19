<?php
namespace  BabDev\PagerfantaBundle\EventListener;

use Pagerfanta\Exception\NotValidMaxPerPageException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ConvertNotValidMaxPerPageToNotFoundListener implements EventSubscriberInterface
{
    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onException(GetResponseForExceptionEvent $event)
    {
        if (method_exists($event, 'getThrowable')) {
            $throwable = $event->getThrowable();
        } else {
            // Support for Symfony 4.3 and before
            $throwable = $event->getException();
        }

        if ($throwable instanceof NotValidMaxPerPageException) {
            $notFoundHttpException = new NotFoundHttpException('Page Not Found', $throwable);
            if (method_exists($event, 'setThrowable')) {
                $event->setThrowable($notFoundHttpException);
            } else {
                // Support for Symfony 4.3 and before
                $event->setException($notFoundHttpException);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onException', 512)
        );
    }
}
