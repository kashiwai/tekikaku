// ============================================
// テキカク不動産 - 共通JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', () => {

  // ============================================
  // ナビゲーション: スクロールで背景変更
  // ============================================
  const nav = document.querySelector('.nav');

  if (nav && !nav.classList.contains('nav--page')) {
    const handleNavScroll = () => {
      if (window.scrollY > 40) {
        nav.classList.add('scrolled');
        nav.classList.remove('transparent');
      } else {
        nav.classList.remove('scrolled');
        nav.classList.add('transparent');
      }
    };

    // 初期状態
    nav.classList.add('transparent');
    handleNavScroll();
    window.addEventListener('scroll', handleNavScroll, { passive: true });
  }

  // ============================================
  // ハンバーガーメニュー
  // ============================================
  const hamburger = document.querySelector('.nav__hamburger');
  const mobileMenu = document.querySelector('.nav__mobile');

  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('active');
      mobileMenu.classList.toggle('open');
      // body スクロール制御
      document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
    });

    // モバイルメニューのリンクをクリックしたら閉じる
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }

  // ============================================
  // スムーズスクロール（アンカーリンク）
  // ============================================
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const href = anchor.getAttribute('href');
      if (href === '#') return;

      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        const navHeight = 70;
        const top = target.getBoundingClientRect().top + window.scrollY - navHeight;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });

  // ============================================
  // アコーディオン（FAQ）
  // ============================================
  document.querySelectorAll('.accordion__header').forEach(header => {
    header.addEventListener('click', () => {
      const item = header.closest('.accordion__item');
      const isOpen = item.classList.contains('open');

      // 同じアコーディオン内の他をすべて閉じる
      const accordion = item.closest('.accordion');
      if (accordion) {
        accordion.querySelectorAll('.accordion__item').forEach(i => {
          i.classList.remove('open');
        });
      }

      // クリックしたものをトグル
      if (!isOpen) {
        item.classList.add('open');
      }
    });
  });

  // ============================================
  // スクロールアニメーション（Intersection Observer）
  // ============================================
  const fadeElements = document.querySelectorAll('[data-fade]');

  if (fadeElements.length > 0 && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    fadeElements.forEach(el => {
      el.classList.add('fade-init');
      observer.observe(el);
    });
  }

  // ============================================
  // お問い合わせフォーム バリデーション（contact.htmlのみ）
  // ============================================
  const contactForm = document.querySelector('#contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const name = contactForm.querySelector('[name="name"]');
      const tel = contactForm.querySelector('[name="tel"]');
      const email = contactForm.querySelector('[name="email"]');
      let valid = true;

      // エラー表示リセット
      contactForm.querySelectorAll('.form-error').forEach(el => el.remove());
      contactForm.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-error'));

      const showError = (field, msg) => {
        field.classList.add('is-error');
        const err = document.createElement('p');
        err.className = 'form-error';
        err.style.cssText = 'color:#E06520;font-size:0.8rem;margin-top:6px;';
        err.textContent = msg;
        field.parentNode.appendChild(err);
        valid = false;
      };

      if (!name.value.trim()) showError(name, 'お名前を入力してください');
      if (!tel.value.trim()) showError(tel, '電話番号を入力してください');
      if (!email.value.trim()) {
        showError(email, 'メールアドレスを入力してください');
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        showError(email, '正しいメールアドレスを入力してください');
      }

      if (valid) {
        // 送信処理（実際のバックエンド連携時に実装）
        const submitBtn = contactForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = '送信中...';

        // デモ: 送信完了メッセージ表示
        setTimeout(() => {
          contactForm.innerHTML = `
            <div style="text-align:center;padding:48px 24px;">
              <div style="font-size:3rem;margin-bottom:16px;">✓</div>
              <h3 style="font-size:1.3rem;font-weight:700;margin-bottom:12px;">送信が完了しました</h3>
              <p style="color:#6B7280;line-height:1.8;">
                お問い合わせいただきありがとうございます。<br>
                担当者より24時間以内にご連絡いたします。
              </p>
            </div>
          `;
        }, 800);
      }
    });
  }

});

// ============================================
// フェードアニメーション CSS（動的挿入）
// ============================================
const fadeCSS = document.createElement('style');
fadeCSS.textContent = `
  .fade-init {
    /* 初期は可視状態 — スクロールで入ったらアニメーション */
    opacity: 1;
    transform: translateY(0);
    transition: opacity 0.6s ease, transform 0.6s ease;
  }
  .fade-init.is-visible {
    opacity: 1;
    transform: translateY(0);
  }
  .form-control.is-error {
    border-color: #E06520;
  }
`;
document.head.appendChild(fadeCSS);
