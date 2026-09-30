@extends('public.layout')

@section('title', '소개 — ' . config('app.name'))
@section('meta_description', config('app.name') . ' 소개')

@section('content')
<article class="prose prose-neutral max-w-none">
    <h1>{{ config('app.name') }} 소개</h1>
    <p>{{ config('app.name') }}는 미국, 특히 조지아·애틀랜타 지역에서 생활하는 한인들과 이주를 준비 중인 분들을 위한 생활 정보 사이트입니다. 이사, 학교, 세금, 보험, 부동산, 생활비 절약 등 실생활에 바로 도움이 되는 정보를 다룹니다.</p>
    <p>Awesome Korean 커뮤니티의 자매 사이트로 운영되고 있습니다.</p>
    <h2>문의</h2>
    <p><a href="mailto:info@awesomekorean.com">info@awesomekorean.com</a></p>
</article>
@endsection
