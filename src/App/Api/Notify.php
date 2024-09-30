<?php
namespace App\Api;

use App\Db\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tk\Db\Filter;

class Notify
{

    public function doGetNotifications(): JsonResponse
    {
        $result = [];
        $user = User::getAuthUser();
        if ($user instanceof User) {
            $notices = \App\Db\Notify::findFiltered(Filter::create([
                'userId' => $user->userId,
                'isRead' => false,
                'isNotified' => false
            ], '-created'));
            \App\Db\Notify::setNotified(array_map(fn($n) => $n->notifyId, $notices));
            // limit notices to a maximum
            $notices = array_splice($notices, 0, 3);
            $result['notices'] = $notices;
        }
        return new JsonResponse($result, Response::HTTP_OK);
    }

}
