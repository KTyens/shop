export const SUPPORT_EMAIL = 'support@crtlu.me';

const configuredWhatsAppNumber = import.meta.env.PUBLIC_CRTLU_WHATSAPP_NUMBER || '';

export const WHATSAPP_NUMBER = String(configuredWhatsAppNumber).replace(/[^\d]/g, '');

export function whatsappUrl(message: string) {
  if (!WHATSAPP_NUMBER) return '';
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
}
