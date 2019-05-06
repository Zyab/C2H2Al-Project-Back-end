<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Post;
use App\Tag;
use App\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth:api', ['except' => ['login', 'register']]);
	}

	/**
	 * Get a JWT via given credentials.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */

	public function login(Request $request)
	{
		$credentials = $request->only('email', 'password');

		if ($token = $this->guard()->attempt($credentials)) {
			return $this->respondWithToken($token);
		}

		return response()->json(['error' => 'Sai tên tài khoản hoặc mật khẩu'], 401);
	}

	/**
	 * Get the authenticated User.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function me()
	{
		return response()->json($this->guard()->user(), 200);
	}

	/**
	 * Log the user out (Invalidate the token).
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function logout()
	{
		$this->guard()->logout();

		return response()->json(['message' => 'Successfully logged out']);
	}

	/**
	 * Refresh a token.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function refresh()
	{
		return $this->respondWithToken($this->guard()->refresh());
	}

	/**
	 * Get the token array structure.
	 *
	 * @param string $token
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function respondWithToken($token)
	{
		return response()->json([
			'access_token' => $token,
			'token_type' => 'bearer',
			'expires_in' => $this->guard()->factory()->getTTL() * 60,
		]);
	}

	/**
	 * Get the guard to be used during authentication.
	 *
	 * @return \Illuminate\Contracts\Auth\Guard
	 */
	public function guard()
	{
		return Auth::guard('api');
	}

	public function register(RegisterRequest $request)
	{
		$user = User::create($request->all());
		return $this->login($request);
	}

	public function update(Request $request)
	{
		$user = $this->guard()->user();
		$user->fill($request->all());
		if ($request->image) {
			$image = $request->image;
			$path = Storage::disk('public')->put('image', $image);
			$user->image = $path;
		}
		$user->save();
		return $user;
	}

	public function createBlog(Request $request)
	{
		$user = $this->guard()->user();
		$post = new Post();
		$post->title = $request->title;
		$post->description = $request->description;
		$post->content = $request->contents;
		$post->user_id = $user->id;
		if ($request->image) {
			$image = $request->image;
			$path = Storage::disk('public')->put('image', $image);
			$post->image = $path;
		}
		$post->save();
		$i = 0;
		$tagReq = 'tag' . $i;
		$tags = Tag::all();
		while ($request->$tagReq !== null) {
			if (!$tags->contains('name', $request->$tagReq)) {
				$tag = new Tag();
				$tag->name = $request->$tagReq;
				$tag->save();
				$post->tag()->attach( $tag->id);
			} else {
				$tag = Tag::where('name', $request->$tagReq)->get();
				$post->tag()->attach( $tag[0]->id);
			}
			$i++;
			$tagReq = 'tag' . $i;

		}
		return response()->json($post);
	}

	public function showBlogs()
	{
		$user = $this->guard()->user();
		$posts = Post::where('user_id', '=', $user->id)
			->orderBy('id', 'DESC')
			->get();
		return $posts;
	}

	public function deleteBlog($id)
	{
		$user = $this->guard()->user();
		$post = Post::findOrFail($id);
		$post->delete();
		return response()->json('Delete successfully');
	}

	public function showBlogDetail($id)
	{
		$user = $this->guard()->user();
		$post = Post::findOrFail($id);
		$post->tag;
		return $post;
	}

	public function updateBlog(Request $request, $id)
	{
		$user = $this->guard()->user();
		$post = Post::findOrFail($id);
		$post->title = $request->title;
		$post->description = $request->description;
		$post->content = $request->contents;
		$post->user_id = $user->id;
		if ($request->image) {
			$image = $request->image;
			$path = Storage::disk('public')->put('image', $image);
			$post->image = $path;
		}
		$i = 0;
		$tagReq = 'tag' . $i;
		$tags = Tag::all();
		while ($request->$tagReq !== null) {
			if (!$tags->contains('name', $request->$tagReq)) {
				$tag = new Tag();
				$tag->name = $request->$tagReq;
				$tag->save();
				$tagId[$i] = $tag->id;
			} else {
				$tag = Tag::where('name', $request->$tagReq)->get();
				$tagId[$i] = $tag[0]->id;
			}
			$i++;
			$tagReq = 'tag' . $i;

		}
		$post->tag()->sync($tagId);
		$post->save();
		return response()->json($post);
	}

	public function search(Request $request)
	{
		$user = $this->guard()->user();
		$keyword = $request->keyWords;

		if (!$keyword) {

			return response()->json('Khong co ket qua');

		}

		$posts = Post::where('user_id', $user->id)
			->where(function ($query) use ($keyword) {
				$query->where('title', 'LIKE', '%' . $keyword . '%')
					->orWhere('description', 'LIKE', '%' . $keyword . '%')
					->orWhere('content', 'LIKE', '%' . $keyword . '%');
			})->get();
		return $posts;
	}

	public function getAllTags()
	{
		$user = $this->guard()->user();
		$allTags = Tag::all();
		return $allTags;
	}

}
