<?php
/*
 * PSX is an open source PHP framework to develop RESTful APIs.
 * For the current version and information visit <https://phpsx.org>
 *
 * Copyright 2010-2020 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response as AmpResponse;
use PSX\Engine\DispatchInterface;
use PSX\Http\Request;
use PSX\Http\Server\ResponseFactory;
use PSX\Http\Stream\StringStream;
use PSX\Uri\Uri;

/**
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Handler implements RequestHandler
{
    private DispatchInterface $dispatch;

    public function __construct(DispatchInterface $dispatch)
    {
        $this->dispatch = $dispatch;
    }
    public function handleRequest(AmpRequest $ampRequest): AmpResponse
    {
        $request = new Request(Uri::parse($ampRequest->getUri()->__toString()), $ampRequest->getMethod(), $ampRequest->getHeaders());
        $response = (new ResponseFactory())->createResponse();

        // read body
        if (in_array($ampRequest->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $body = $ampRequest->getBody()->read();
            $request->setBody(new StringStream($body));
        }

        $response = $this->dispatch->route($request, $response);

        return new AmpResponse($response->getStatusCode() ?: 200, $response->getHeaders(), $response->getBody()->__toString());
    }
}