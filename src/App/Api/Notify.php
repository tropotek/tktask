<?php
namespace App\Api;

use App\Db\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tk\Date;
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

            // limit notices to a maximum
            $notices = array_splice($notices, 0, 5);
            $result['notices'] = $notices;

            \App\Db\Notify::setNotified(array_map(fn($n) => $n->notifyId, $result['notices']));
        }
        return new JsonResponse($result, Response::HTTP_OK);
    }

    public function doMarkRead(): JsonResponse
    {
        $result = [];
        $notifyId = intval($_POST['notifyId'] ?? 0);

        $notify = \App\Db\Notify::find($notifyId);
        if ($notify instanceof \App\Db\Notify && $notify->userId == User::getAuthUser()->userId) {
            $notify->readAt = Date::create();
            $notify->save();
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

}
