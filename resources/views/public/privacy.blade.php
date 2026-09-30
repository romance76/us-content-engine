@extends('public.layout')

@section('title', '개인정보처리방침 — ' . config('app.name'))
@section('meta_description', config('app.name') . ' 개인정보처리방침')

@section('content')
<article class="prose prose-neutral max-w-none">
    <h1>개인정보처리방침</h1>
    <p class="text-sm text-gray-400">최종 수정일: {{ now()->format('Y년 m월 d일') }}</p>

    <p>{{ config('app.name') }}(이하 "본 사이트")는 방문자의 개인정보를 소중히 여기며, 아래와 같은 방침에 따라 정보를 수집·이용합니다.</p>

    <h2>1. 수집하는 정보</h2>
    <p>본 사이트는 별도의 회원가입 없이 이용하실 수 있으며, 게시물 열람 과정에서 다음 정보가 자동으로 수집될 수 있습니다.</p>
    <ul>
        <li>접속 IP, 브라우저 종류, 방문 페이지, 방문 시간 등 서버 로그 정보</li>
        <li>쿠키(Cookie) 및 이와 유사한 기술을 통한 방문 기록</li>
    </ul>

    <h2>2. 쿠키와 광고</h2>
    <p>본 사이트는 콘텐츠 제공을 위해 Google AdSense 등 제3자 광고 서비스를 사용할 수 있습니다. Google을 비롯한 제3자 공급업체는 쿠키를 사용하여 사용자의 이전 방문 기록을 기반으로 광고를 게재합니다. 사용자는 <a href="https://adssettings.google.com/" target="_blank" rel="noopener">Google 광고 설정</a> 페이지에서 맞춤 광고를 비활성화할 수 있습니다.</p>

    <h2>3. 정보의 이용 목적</h2>
    <ul>
        <li>사이트 이용 통계 분석 및 서비스 개선</li>
        <li>맞춤형 광고 제공</li>
        <li>부정 이용 방지 및 보안</li>
    </ul>

    <h2>4. 정보의 제3자 제공</h2>
    <p>본 사이트는 법령에 특별한 규정이 있는 경우를 제외하고 수집한 정보를 외부에 제공하지 않습니다. 다만 광고 서비스 운영을 위해 Google 등 광고 파트너에게 익명화된 쿠키 정보가 전달될 수 있습니다.</p>

    <h2>5. 문의</h2>
    <p>개인정보 처리와 관련한 문의사항은 아래 이메일로 연락해 주세요.</p>
    <p><a href="mailto:privacy@awesomekorean.com">privacy@awesomekorean.com</a></p>
</article>
@endsection
