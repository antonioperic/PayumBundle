<?php
namespace Payum\Bundle\PayumBundle\Tests\Controller;

use Payum\Bundle\PayumBundle\Controller\AuthorizeController;
use Payum\Core\Model\Token;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class AuthorizeControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfController()
    {
        $rc = new \ReflectionClass('Payum\Bundle\PayumBundle\Controller\AuthorizeController');

        $this->assertTrue($rc->isSubclassOf('Symfony\Bundle\FrameworkBundle\Controller\Controller'));
    }

    /**
     * @test
     */
    public function shouldExecuteAuthorizeRequest()
    {
        $request = Request::create('/');
        $request->query->set('foo', 'fooVal');

        $token = new Token;
        $token->setPaymentName('thePayment');
        $token->setAfterUrl('http://example.com/theAfterUrl');

        $tokenVerifierMock = $this->getMock('Payum\Core\Security\HttpRequestVerifierInterface');
        $tokenVerifierMock
            ->expects($this->once())
            ->method('verify')
            ->with($this->identicalTo($request))
            ->will($this->returnValue($token))
        ;
        $tokenVerifierMock
            ->expects($this->once())
            ->method('invalidate')
            ->with($this->identicalTo($token))
        ;

        $paymentMock = $this->getMock('Payum\Core\PaymentInterface');
        $paymentMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\SecuredAuthorize'))
        ;

        $registryMock = $this->getMock('Payum\Core\Registry\RegistryInterface');
        $registryMock
            ->expects($this->once())
            ->method('getPayment')
            ->with('thePayment')
            ->will($this->returnValue($paymentMock))
        ;

        $container = new Container;
        $container->set('payum', $registryMock);
        $container->set('payum.security.http_request_verifier', $tokenVerifierMock);

        $controller = new AuthorizeController;
        $controller->setContainer($container);

        $response = $controller->doAction($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals('http://example.com/theAfterUrl', $response->getTargetUrl());
    }
}
