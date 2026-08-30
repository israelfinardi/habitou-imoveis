// Service worker: keeps the last extraction cached so the popup can be
// closed and reopened without losing the result.
chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.local.set({ lastExtraction: null });
});
