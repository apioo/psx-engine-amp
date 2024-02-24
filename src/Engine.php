<?php
/*
 * PSX is an open source PHP framework to develop RESTful APIs.
 * For the current version and information visit <https://phpsx.org>
 *
 * Copyright 2010-2024 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PSX\Engine\Amp;

use Amp;
use Amp\ByteStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PSX\Engine\DispatchInterface;
use PSX\Engine\EngineInterface;

/**
 * Uses the AMP HTTP server
 *
 * @see     https://github.com/amphp/http-server
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Engine implements EngineInterface
{
    private string $ip;
    private int $port;

    public function __construct(string $ip = '0.0.0.0', int $port = 8080)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function serve(DispatchInterface $dispatch): void
    {
        $logHandler = new StreamHandler(ByteStream\getStdout());
        $logHandler->pushProcessor(new PsrLogMessageProcessor());
        $logHandler->setFormatter(new ConsoleFormatter());

        $logger = new Logger('server');
        $logger->pushHandler($logHandler);

        $requestHandler = new Handler($dispatch);
        $errorHandler = new DefaultErrorHandler();

        $server = SocketHttpServer::createForDirectAccess($logger);
        $server->expose($this->ip . ':' . $this->port);
        $server->start($requestHandler, $errorHandler);

        Amp\trapSignal([SIGINT, SIGTERM]);

        $server->stop();
    }
}
