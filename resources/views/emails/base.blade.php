<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: sans-serif; padding: 20px; background: #f7f7f7; }
    .box { background: #fff; padding: 20px; border-radius: 8px; }
    .btn { display: inline-block; padding: 10px 15px; background: #007BFF; color: #fff; text-decoration: none; border-radius: 4px; }
  </style>
</head>
<body>
  <div class="box">
    <h2>{{ $title }}</h2>
    <p>{{ $message }}</p>
    @if(isset($action_url))
      <a href="{{ $action_url }}" class="btn">{{ $action_text ?? 'Ver más' }}</a>
    @endif
  </div>
</body>
</html>
