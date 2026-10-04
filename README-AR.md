# عمليات النشر عبر Laravel Forge

حزمة Laravel عامة توفر لوحة محمية لتشغيل نشر موقع واحد محدد مسبقاً في [Laravel Forge](https://forge.laravel.com)، مع عرض الحالة والسجل والمخرجات النصية وتوثيق هوية المستخدم الذي بدأ النشر، دون منحه بيانات دخول الخادم أو حساب Forge.

[English](README.md)

## أهم الخصائص

- يملك التطبيق المضيف المصادقة ويحدد الصلاحية من خلال Laravel Gate إلزامية.
- جميع مسارات الصفحة وطلبات JSON تمر عبر middleware المصادقة وmiddleware التفويض الخاص بالحزمة.
- تحدد إعدادات الخادم هدفاً واحداً فقط؛ لا يستطيع المتصفح إرسال Server ID أو Site ID بديل.
- يمنع قفل موزع مع تحقق مباشر من حالة Forge تشغيل عمليتي نشر متداخلتين.
- لا تعيد الحزمة طلب بدء النشر تلقائياً، لأن الطلب غير قابل للتكرار الآمن.
- يسجل جدول محلي هوية المنفذ، بينما يبقى Forge مصدر الحقيقة للحالة والسجل والمخرجات.
- واجهة مستقلة ومتجاوبة تدعم الوضعين الفاتح والداكن واللغتين العربية والإنجليزية دون الاعتماد على Bootstrap أو Tailwind.

## المتطلبات

- PHP 8.2 أو 8.3 أو 8.4 أو 8.5
- Laravel 10 أو 11 أو 12 أو 13 (قد يفرض إصدار Laravel المستخدم حداً أدنى أعلى لإصدار PHP)
- Cache driver يدعم الأقفال الذرية عند تشغيل أكثر من process أو خادم
- API token من Forge بأقل صلاحيات لازمة

> **التوافق مع الإصدارات القديمة:** تحتفظ الحزمة بالتوافق مع Laravel 10 وLaravel 11، لكنهما خارج فترة الدعم الأمني الرسمية وقد يمنع Composer تثبيتهما عند وجود تحذيرات أمنية معروفة. يفضّل استخدام إصدار Laravel مدعوم في الإنتاج. راجع [سياسة الدعم الرسمية](https://laravel.com/docs/13.x/releases#support-policy).

## التثبيت

```bash
composer require omaralalwi/laravel-forge-deployments
php artisan vendor:publish --tag=laravel-forge-deployments-config
php artisan migrate
```

عرّف Gate داخل `App\Providers\AppServiceProvider` بحسب نظام الصلاحيات في تطبيقك:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('manageForgeDeployments', function (User $user): bool {
        return $user->hasPermissionTo('manage deployments');
    });
}
```

ترفض الحزمة الطلب بالرمز HTTP 403 إذا لم يوجد مستخدم مصادق، أو لم تُعرّف Gate، أو رفضت Gate المستخدم.

## إنشاء Token ومعرفة المعرفات

افتح [صفحة API في Forge](https://forge.laravel.com/profile/api)، وأنشئ رمزاً باسم واضح ومدة مناسبة، وحدد أقل الصلاحيات اللازمة لقراءة المؤسسة والخادم والموقع وعمليات النشر وبدء عملية نشر. يشرح Forge الخطوات في [توثيق API الرسمي](https://laravel.com/forge/docs/api#authentication).

ضع الرمز مؤقتاً في متغير البيئة:

```dotenv
LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN=your-secret-token
```

ثم شغّل:

```bash
php artisan forge-deployments:discover
```

سيعرض الأمر Organization Slug وServer ID وSite ID المتاحة للرمز دون طباعته. في Forge API v2 يسمى التطبيق المستضاف **Site**، لذلك القيمة التي قد تسمى أحياناً App ID توضع في `SITE_ID`. يوضح [دليل Forge SDK](https://laravel.com/forge/docs/sdk#resources) تسلسل المؤسسة ثم الخادم ثم الموقع.

## متغيرات البيئة

```dotenv
LARAVEL_FORGE_DEPLOYMENTS_ENABLED=true
LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN=your-secret-token
LARAVEL_FORGE_DEPLOYMENTS_ORGANIZATION_SLUG=your-organization
LARAVEL_FORGE_DEPLOYMENTS_SERVER_ID=123456
LARAVEL_FORGE_DEPLOYMENTS_SITE_ID=789012
LARAVEL_FORGE_DEPLOYMENTS_TARGET_LABEL="Staging"
LARAVEL_FORGE_DEPLOYMENTS_BRANCH=develop
```

لا تستخدم بادئة `FORGE_` لمتغيرات الحزمة، لأن Forge يحجزها لمتغيرات يضيفها وقت تنفيذ سكربت النشر، كما يوضح [توثيق متغيرات بيئة النشر](https://laravel.com/forge/docs/sites/deployments#environment-variables).

الفرع المعروض وصفي؛ ينفذ Forge الفرع وسكربت النشر المضبوطين في الموقع. راجع [توثيق عمليات النشر](https://laravel.com/forge/docs/sites/deployments) قبل التفعيل.

```bash
php artisan config:cache
php artisan forge-deployments:check
```

يفحص الأمر الاتصال والحالة الحالية فقط ولا يبدأ أي نشر. المسار الافتراضي للوحة هو `/forge-deployments`.

لخطوات أكثر تفصيلاً راجع [دليل إعداد Forge](docs/forge-setup.md) و[نموذج الأمان](docs/security-model.md).

## تخصيص الحارس والمسار

يمكن تعديل `config/forge-deployments.php` بعد نشره. مثلاً لتطبيق يستخدم admin guard:

```php
'route' => [
    'prefix' => 'admin/forge-deployments',
    'name_prefix' => 'forge-deployments.',
    'middleware' => ['web', 'auth:admin'],
],
```

يمكن نشر الواجهات والترجمات:

```bash
php artisan vendor:publish --tag=laravel-forge-deployments-views
php artisan vendor:publish --tag=laravel-forge-deployments-translations
```

## الاختبارات

```bash
composer install
composer format -- --test
composer test
```

تعمل الاختبارات حصراً على SQLite داخل الذاكرة.

## الترخيص

الحزمة مفتوحة المصدر تحت [ترخيص MIT](LICENSE).
