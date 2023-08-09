<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\HttpResponses;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use mysql_xdevapi\Exception;

class CategoryController extends Controller
{
    use HttpResponses;
    private $sorting;
    private $pagesize;

    private $search;

//    private $page;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->sorting = $request->query('sort');
        $this->pagesize = $request->query('pagesize');
        $this->search = $request->query('search');
        $this->page = $request->query('page');

//        dd($this->page);


        if ($this->sorting=='name') {
            $categories = Category::where('name','like','%'.$this->search .'%')->orderBy('name','ASC')->paginate($this->pagesize);
        }
        else if ($this->sorting=='name-desc') {
            $categories = Category::where('name','like','%'.$this->search .'%')->orderBy('name','DESC')->paginate($this->pagesize);
        }
        else if ($this->sorting=='date'){
            $categories = Category::where('name','like','%'.$this->search .'%')->orderBy('created_at','DESC')->paginate($this->pagesize);
        }
        else{
            $categories = Category::where('name','like','%'.$this->search .'%')->paginate($this->pagesize);
        }

        return $this->success($categories,null , 200 );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCategoryRequest $request)
    {
        $slug = Str::slug($request->name);


            $category = Category::firstOrCreate(
                ['name' => $request->name],
                ['slug' => $slug]
            );

            if (!$category->wasRecentlyCreated) {
                // Nếu danh mục đã tồn tại, trả về status code 409 Conflict
                return $this->error(null , 'Danh mục đã tồn tại.' , 409);
            }
            return $this->success($category, 'Tạo thành công', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            // Xử lý trường hợp không tìm thấy category với id tương ứng
            return response()->json(['message' => 'Category not found'], 404);
        }

        return new CategoryResource($category);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        // Kiểm tra xem danh mục có tồn tại trong database không
        $category = Category::find($id);
        if (!$category) {
            // Nếu danh mục không tồn tại, trả về phản hồi lỗi
            return $this->error(null, 'Không tìm thấy danh mục', 404);
        }
        $slug = Str::slug($request->name);
        // Kiểm tra xem slug mới đã tồn tại trong cơ sở dữ liệu hay chưa
        $existingCategory = Category::where('slug', $slug)->where('id', '!=', $id)->first();
        if ($existingCategory) {
            // Nếu đã tồn tại, trả về phản hồi lỗi
            return $this->error(null, 'Name đã tồn tại cho một danh mục khác', 409);
        }
        $category->update([
            'name' => $request->name,
            'slug' => $slug
        ]);

        return $this->success($category, 'Cập nhật thành công', 200);


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

            // Tìm danh mục theo $id
            $category = Category::find($id);
            if (!$category) {
                // Nếu danh mục không tồn tại, trả về phản hồi lỗi
                return $this->error(null, 'Không tìm thấy danh mục', 404);
            }

            // Xóa danh mục
            $category->delete();

            // Trả về thông báo thành công nếu xóa thành công
            return $this->success($category, 'Xóa danh mục thành công', 200);

    }
}
