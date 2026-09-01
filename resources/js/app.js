import './bootstrap';

import $ from 'jquery';
import Swal from 'sweetalert2';

import { initChat } from './ui/chat';
import { initDrawer } from './ui/drawer';
import { initLoader, showLoader, hideLoader } from './ui/loader';
import { initMenus } from './ui/menu';
import { initParallax } from './ui/parallax';
import { initPasswordToggles } from './ui/password';
import { initTheme, toggleTheme } from './ui/theme';
import { initValidation } from './ui/validation';
import { initVoice } from './ui/voice';
import {
    initAccountDeleteConfirm,
    initConversationDeleteConfirm,
    initFlashAlerts,
    initLogoutConfirm,
    initResendConfirm,
    toast,
    success,
    error,
} from './ui/alerts';

window.$ = window.jQuery = $;
window.Swal = Swal;
window.appUI = { showLoader, hideLoader, toast, success, error, toggleTheme };

$(function () {
    initTheme();
    initDrawer();
    initLoader();
    initMenus();
    initParallax();
    initPasswordToggles();
    initValidation();
    initFlashAlerts();
    initLogoutConfirm();
    initResendConfirm();
    initConversationDeleteConfirm();
    initAccountDeleteConfirm();
    initChat();
    initVoice();
});
