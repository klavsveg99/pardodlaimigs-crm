@extends('site.layout')

@section('content')
    <section class="section content-page">
        <div class="container content-layout">
            <h1>{{ $page['title'] }}</h1>
            <div class="post-body">
                {!! $page['html'] !!}
            </div>
        </div>
    </section>
@endsection
