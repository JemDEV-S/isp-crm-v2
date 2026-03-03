@extends('accesscontrol::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('accesscontrol.name') !!}</p>
@endsection
