@extends('network::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('network.name') !!}</p>
@endsection
