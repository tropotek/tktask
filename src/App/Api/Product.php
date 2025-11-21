<?php
namespace App\Api;

use App\Db\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Product
{

    public function doGetProduct(): JsonResponse
    {
        $user = User::getAuthUser();
        if (!$user->isStaff()) {
            return new JsonResponse(['status' => 'err'], Response::HTTP_UNAUTHORIZED);
        }

        $productId = intval($_GET['productId'] ?? 0);
        $product = \App\Db\Product::find($productId);
        if (is_null($product)) {
            return new JsonResponse(['status' => 'err'], Response::HTTP_NOT_FOUND);
        }
        $obj = (object)[
            'productId' => $product->productId,
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->getProductCategory()->name,
            'price' => $product->price->toFloatString(),
            'description' => $product->description,
            'cycle' => $product->cycle,
        ];
        return new JsonResponse($obj, Response::HTTP_OK);
    }

}
