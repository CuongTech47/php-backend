<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
       return [
         'id' => $this->id,

           'attributes'=>[
               'name'=>$this->name,
               'description'=>$this->description,
               'regular_price'=>$this->regular_price
           ],
//           'relationships'=>[
//               'id'=>$this->category->id,
//               'name'=>$this->category->name
//           ]
       ];
    }
}
