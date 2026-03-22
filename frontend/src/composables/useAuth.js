import { reactive, readonly } from 'vue';

const state = reactive({
  accessToken: null,
  user: null,
  loading: false
});

let refreshPromise = null;

function setAccessToken(token) {
  state.accessToken = token || null;
}

function clearAuth() {
  setAccessToken(null);
  state.user = null;
}

export async function authFetch(input, init = {}) {
  async function doFetch(triedRefresh = false) {
    const options = { ...init };
    options.headers = { ...(options.headers || {}) }

    if(state.accessToken) {
      options.headers = { ...options.headers, Authorization: `Bearer ${state.accessToken}` };
    }

    if(!options.credentials) options.credentials = 'include';

    const res = await fetch(input, options);

    if (res.status === 401 && !triedRefresh) {
      const r = await refresh();
      if(r.success && state.accessToken) {
        return doFetch(true);
      }
    }

    return res;
  }

  return doFetch(false);
}

export async function login({ email, password }) {
  state.loading = true;
  try {
    const res = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
      credentials: 'include',
    });

    if (!res.ok) {
      const text = await parseErrorResponse(res);
      throw new Error(text || `Status ${res.status}`);
    }

    const result = await res.json();
    const data = result.data;
    if (data.access_token) {
      setAccessToken(data.access_token);
    }
    state.user = data.user || null;
    console.log('[useAuth] login -> data =', data.user)
    return { success: true, data: result.data };
  } catch (err) {
    return { success: false, error: err.message || 'Login failed' };
  } finally {
    state.loading = false;
  }
}

export async function register(payload) {
  state.loading = true;
  try {
    const res = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'include',
    });

    if (!res.ok) {
      const text = await parseErrorResponse(res);
      throw new Error(text || `Status ${res.status}`);
    }

    const result = await res.json();
    const data = result.data;
    if (data.access_token) {
      setAccessToken(data.access_token);
    }
    state.user = data.user || null;
    return { success: true, data };
  } catch (err) {
    return { success: false, error: err.message || 'Registration failed' };
  } finally {
    state.loading = false;
  }
}

export async function refresh() {
  if (refreshPromise) {
    return refreshPromise;
  }

  state.loading = true;
  refreshPromise = (async () => {
    try {
      const res = await fetch('/api/auth/refresh', {
        method: 'POST',
        credentials: 'include',
      });

      if (!res.ok) {
        clearAuth();
        throw new Error(`Refresh failed: ${res.status}`);
      }

      const result = await res.json();
      const data = result.data;
      if (data.access_token) {
        setAccessToken(data.access_token);
      }
      state.user = data.user || null;
      return { success: true, data }
    } catch (err) {
      return { success: false, error: err.message || 'Refresh failed' }
    } finally {
      state.loading = false;
      refreshPromise = null;
    }
  })();
  return refreshPromise;
}

export async function logout() {
  state.loading = true;
  try {
    const res = await fetch('/api/auth/logout', {
      method: 'POST',
      credentials: 'include',
    });

    if (!res.ok) {
      const text = await res.text();
      throw new Error(text || `Status ${res.status}`);
    }

    state.accessToken = null;
    state.user = null;
    return { success: true }
  } catch (err) {
    return { success: false, error: err.message || 'Logout failed' }
  } finally {
    state.loading = false;
  }
}

export function useAuth() {
  return {
    state: readonly(state),
    login,
    register,
    refresh,
    logout,
    authFetch
  }
}


async function parseErrorResponse(res) {
  try {
    const contentType = res.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
      const body = await res.json();
      return body.message || body.error || JSON.stringify(body);
    }
    const text = await res.text();
    try {
      const parsed = JSON.parse(text);
      return parsed.message || parsed.error || text;
    } catch {
      return text || `Status ${res.status}`;
    }
  } catch {
    return `Status ${res.status}`;
  }
}
