<?php

namespace Nicolasliu\Laravelfilemanager\middlewares;

use Nicolasliu\Laravelfilemanager\traits\LfmHelpers;
use Closure;

class CreateDefaultFolder
{
    use LfmHelpers;

    public function handle($request, Closure $next)
    {
        $this->checkDefaultFolderExists('user');
        $this->checkDefaultFolderExists('share');

        return $next($request);
    }

    private function checkDefaultFolderExists($type = 'share')
    {
        if ($type === 'user' && !$this->allowMultiUser()) {
            return;
        }

        if ($type === 'share' && (!$this->enabledShareFolder()  || !$this->allowMultiUser())) {
            return;
        }

        $path = $this->getRootFolderPath($type);

        $this->createFolderByPath($path);
    }
}
