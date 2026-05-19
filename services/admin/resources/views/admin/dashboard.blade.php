@extends('admin.layout', ['title' => 'Dashboard'])

@section('content')
    <div class="top">
        <h1>Dashboard</h1>
    </div>
    <div class="grid">
        <div class="stat">Posts<strong>{{ $posts }}</strong></div>
        <div class="stat">Categories<strong>{{ $categories }}</strong></div>
        <div class="stat">Tags<strong>{{ $tags }}</strong></div>
    </div>
@endsection
