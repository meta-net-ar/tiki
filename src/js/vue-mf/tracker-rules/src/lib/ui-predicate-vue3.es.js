import { inject as ie, computed as Fs, createElementBlock as Q, openBlock as W, createElementVNode as fe, createBlock as ge, resolveDynamicComponent as Te, unref as ue, resolveComponent as be, createVNode as ai, h as Bs, createCommentVNode as Ce, Fragment as Se, renderList as Be, toDisplayString as Me, mergeModels as Ai, useModel as Ms, ref as Je, shallowRef as Pi, provide as ce, toRaw as Oi, onMounted as Ls, onBeforeUnmount as Ds } from "vue";
var _e = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : typeof self < "u" ? self : {};
function ui(e) {
  return e && e.__esModule && Object.prototype.hasOwnProperty.call(e, "default") ? e.default : e;
}
var We = {}, ze = {}, He, Ti;
function Gu() {
  return Ti || (Ti = 1, He = function() {
    if (typeof Symbol != "function" || typeof Object.getOwnPropertySymbols != "function")
      return !1;
    if (typeof Symbol.iterator == "symbol")
      return !0;
    var t = {}, r = Symbol("test"), n = Object(r);
    if (typeof r == "string" || Object.prototype.toString.call(r) !== "[object Symbol]" || Object.prototype.toString.call(n) !== "[object Symbol]")
      return !1;
    var i = 42;
    t[r] = i;
    for (var o in t)
      return !1;
    if (typeof Object.keys == "function" && Object.keys(t).length !== 0 || typeof Object.getOwnPropertyNames == "function" && Object.getOwnPropertyNames(t).length !== 0)
      return !1;
    var a = Object.getOwnPropertySymbols(t);
    if (a.length !== 1 || a[0] !== r || !Object.prototype.propertyIsEnumerable.call(t, r))
      return !1;
    if (typeof Object.getOwnPropertyDescriptor == "function") {
      var u = (
        /** @type {PropertyDescriptor} */
        Object.getOwnPropertyDescriptor(t, r)
      );
      if (u.value !== i || u.enumerable !== !0)
        return !1;
    }
    return !0;
  }), He;
}
var Ke, Si;
function Le() {
  if (Si) return Ke;
  Si = 1;
  var e = Gu();
  return Ke = function() {
    return e() && !!Symbol.toStringTag;
  }, Ke;
}
var Xe, Ri;
function Vu() {
  return Ri || (Ri = 1, Xe = Object), Xe;
}
var Ye, qi;
function Ns() {
  return qi || (qi = 1, Ye = Error), Ye;
}
var Ze, wi;
function $s() {
  return wi || (wi = 1, Ze = EvalError), Ze;
}
var Qe, ji;
function Us() {
  return ji || (ji = 1, Qe = RangeError), Qe;
}
var er, Ei;
function ks() {
  return Ei || (Ei = 1, er = ReferenceError), er;
}
var rr, Ii;
function Ju() {
  return Ii || (Ii = 1, rr = SyntaxError), rr;
}
var tr, Ci;
function Re() {
  return Ci || (Ci = 1, tr = TypeError), tr;
}
var nr, Fi;
function xs() {
  return Fi || (Fi = 1, nr = URIError), nr;
}
var ir, Bi;
function Gs() {
  return Bi || (Bi = 1, ir = Math.abs), ir;
}
var or, Mi;
function Vs() {
  return Mi || (Mi = 1, or = Math.floor), or;
}
var ar, Li;
function Js() {
  return Li || (Li = 1, ar = Math.max), ar;
}
var ur, Di;
function Ws() {
  return Di || (Di = 1, ur = Math.min), ur;
}
var sr, Ni;
function zs() {
  return Ni || (Ni = 1, sr = Math.pow), sr;
}
var cr, $i;
function Hs() {
  return $i || ($i = 1, cr = Math.round), cr;
}
var fr, Ui;
function Ks() {
  return Ui || (Ui = 1, fr = Number.isNaN || function(t) {
    return t !== t;
  }), fr;
}
var pr, ki;
function Xs() {
  if (ki) return pr;
  ki = 1;
  var e = /* @__PURE__ */ Ks();
  return pr = function(r) {
    return e(r) || r === 0 ? r : r < 0 ? -1 : 1;
  }, pr;
}
var lr, xi;
function Ys() {
  return xi || (xi = 1, lr = Object.getOwnPropertyDescriptor), lr;
}
var dr, Gi;
function Ae() {
  if (Gi) return dr;
  Gi = 1;
  var e = /* @__PURE__ */ Ys();
  if (e)
    try {
      e([], "length");
    } catch {
      e = null;
    }
  return dr = e, dr;
}
var yr, Vi;
function De() {
  if (Vi) return yr;
  Vi = 1;
  var e = Object.defineProperty || !1;
  if (e)
    try {
      e({}, "a", { value: 1 });
    } catch {
      e = !1;
    }
  return yr = e, yr;
}
var vr, Ji;
function Zs() {
  if (Ji) return vr;
  Ji = 1;
  var e = typeof Symbol < "u" && Symbol, t = Gu();
  return vr = function() {
    return typeof e != "function" || typeof Symbol != "function" || typeof e("foo") != "symbol" || typeof Symbol("bar") != "symbol" ? !1 : t();
  }, vr;
}
var gr, Wi;
function Wu() {
  return Wi || (Wi = 1, gr = typeof Reflect < "u" && Reflect.getPrototypeOf || null), gr;
}
var hr, zi;
function zu() {
  if (zi) return hr;
  zi = 1;
  var e = /* @__PURE__ */ Vu();
  return hr = e.getPrototypeOf || null, hr;
}
var mr, Hi;
function Qs() {
  if (Hi) return mr;
  Hi = 1;
  var e = "Function.prototype.bind called on incompatible ", t = Object.prototype.toString, r = Math.max, n = "[object Function]", i = function(s, c) {
    for (var p = [], f = 0; f < s.length; f += 1)
      p[f] = s[f];
    for (var d = 0; d < c.length; d += 1)
      p[d + s.length] = c[d];
    return p;
  }, o = function(s, c) {
    for (var p = [], f = c, d = 0; f < s.length; f += 1, d += 1)
      p[d] = s[f];
    return p;
  }, a = function(u, s) {
    for (var c = "", p = 0; p < u.length; p += 1)
      c += u[p], p + 1 < u.length && (c += s);
    return c;
  };
  return mr = function(s) {
    var c = this;
    if (typeof c != "function" || t.apply(c) !== n)
      throw new TypeError(e + c);
    for (var p = o(arguments, 1), f, d = function() {
      if (this instanceof f) {
        var j = c.apply(
          this,
          i(p, arguments)
        );
        return Object(j) === j ? j : this;
      }
      return c.apply(
        s,
        i(p, arguments)
      );
    }, P = r(0, c.length - p.length), _ = [], w = 0; w < P; w++)
      _[w] = "$" + w;
    if (f = Function("binder", "return function (" + a(_, ",") + "){ return binder.apply(this,arguments); }")(d), c.prototype) {
      var O = function() {
      };
      O.prototype = c.prototype, f.prototype = new O(), O.prototype = null;
    }
    return f;
  }, mr;
}
var _r, Ki;
function qe() {
  if (Ki) return _r;
  Ki = 1;
  var e = Qs();
  return _r = Function.prototype.bind || e, _r;
}
var br, Xi;
function si() {
  return Xi || (Xi = 1, br = Function.prototype.call), br;
}
var Ar, Yi;
function ci() {
  return Yi || (Yi = 1, Ar = Function.prototype.apply), Ar;
}
var Pr, Zi;
function ec() {
  return Zi || (Zi = 1, Pr = typeof Reflect < "u" && Reflect && Reflect.apply), Pr;
}
var Or, Qi;
function Hu() {
  if (Qi) return Or;
  Qi = 1;
  var e = qe(), t = ci(), r = si(), n = ec();
  return Or = n || e.call(r, t), Or;
}
var Tr, eo;
function fi() {
  if (eo) return Tr;
  eo = 1;
  var e = qe(), t = /* @__PURE__ */ Re(), r = si(), n = Hu();
  return Tr = function(o) {
    if (o.length < 1 || typeof o[0] != "function")
      throw new t("a function is required");
    return n(e, r, o);
  }, Tr;
}
var Sr, ro;
function rc() {
  if (ro) return Sr;
  ro = 1;
  var e = fi(), t = /* @__PURE__ */ Ae(), r;
  try {
    r = /** @type {{ __proto__?: typeof Array.prototype }} */
    [].__proto__ === Array.prototype;
  } catch (a) {
    if (!a || typeof a != "object" || !("code" in a) || a.code !== "ERR_PROTO_ACCESS")
      throw a;
  }
  var n = !!r && t && t(
    Object.prototype,
    /** @type {keyof typeof Object.prototype} */
    "__proto__"
  ), i = Object, o = i.getPrototypeOf;
  return Sr = n && typeof n.get == "function" ? e([n.get]) : typeof o == "function" ? (
    /** @type {import('./get')} */
    function(u) {
      return o(u == null ? u : i(u));
    }
  ) : !1, Sr;
}
var Rr, to;
function Ku() {
  if (to) return Rr;
  to = 1;
  var e = Wu(), t = zu(), r = /* @__PURE__ */ rc();
  return Rr = e ? function(i) {
    return e(i);
  } : t ? function(i) {
    if (!i || typeof i != "object" && typeof i != "function")
      throw new TypeError("getProto: not an object");
    return t(i);
  } : r ? function(i) {
    return r(i);
  } : null, Rr;
}
var qr, no;
function Xu() {
  if (no) return qr;
  no = 1;
  var e = Function.prototype.call, t = Object.prototype.hasOwnProperty, r = qe();
  return qr = r.call(e, t), qr;
}
var wr, io;
function Yu() {
  if (io) return wr;
  io = 1;
  var e, t = /* @__PURE__ */ Vu(), r = /* @__PURE__ */ Ns(), n = /* @__PURE__ */ $s(), i = /* @__PURE__ */ Us(), o = /* @__PURE__ */ ks(), a = /* @__PURE__ */ Ju(), u = /* @__PURE__ */ Re(), s = /* @__PURE__ */ xs(), c = /* @__PURE__ */ Gs(), p = /* @__PURE__ */ Vs(), f = /* @__PURE__ */ Js(), d = /* @__PURE__ */ Ws(), P = /* @__PURE__ */ zs(), _ = /* @__PURE__ */ Hs(), w = /* @__PURE__ */ Xs(), O = Function, j = function(M) {
    try {
      return O('"use strict"; return (' + M + ").constructor;")();
    } catch {
    }
  }, I = /* @__PURE__ */ Ae(), E = /* @__PURE__ */ De(), g = function() {
    throw new u();
  }, h = I ? function() {
    try {
      return arguments.callee, g;
    } catch {
      try {
        return I(arguments, "callee").get;
      } catch {
        return g;
      }
    }
  }() : g, b = Zs()(), A = Ku(), S = zu(), $ = Wu(), C = ci(), R = si(), V = {}, K = typeof Uint8Array > "u" || !A ? e : A(Uint8Array), T = {
    __proto__: null,
    "%AggregateError%": typeof AggregateError > "u" ? e : AggregateError,
    "%Array%": Array,
    "%ArrayBuffer%": typeof ArrayBuffer > "u" ? e : ArrayBuffer,
    "%ArrayIteratorPrototype%": b && A ? A([][Symbol.iterator]()) : e,
    "%AsyncFromSyncIteratorPrototype%": e,
    "%AsyncFunction%": V,
    "%AsyncGenerator%": V,
    "%AsyncGeneratorFunction%": V,
    "%AsyncIteratorPrototype%": V,
    "%Atomics%": typeof Atomics > "u" ? e : Atomics,
    "%BigInt%": typeof BigInt > "u" ? e : BigInt,
    "%BigInt64Array%": typeof BigInt64Array > "u" ? e : BigInt64Array,
    "%BigUint64Array%": typeof BigUint64Array > "u" ? e : BigUint64Array,
    "%Boolean%": Boolean,
    "%DataView%": typeof DataView > "u" ? e : DataView,
    "%Date%": Date,
    "%decodeURI%": decodeURI,
    "%decodeURIComponent%": decodeURIComponent,
    "%encodeURI%": encodeURI,
    "%encodeURIComponent%": encodeURIComponent,
    "%Error%": r,
    "%eval%": eval,
    // eslint-disable-line no-eval
    "%EvalError%": n,
    "%Float16Array%": typeof Float16Array > "u" ? e : Float16Array,
    "%Float32Array%": typeof Float32Array > "u" ? e : Float32Array,
    "%Float64Array%": typeof Float64Array > "u" ? e : Float64Array,
    "%FinalizationRegistry%": typeof FinalizationRegistry > "u" ? e : FinalizationRegistry,
    "%Function%": O,
    "%GeneratorFunction%": V,
    "%Int8Array%": typeof Int8Array > "u" ? e : Int8Array,
    "%Int16Array%": typeof Int16Array > "u" ? e : Int16Array,
    "%Int32Array%": typeof Int32Array > "u" ? e : Int32Array,
    "%isFinite%": isFinite,
    "%isNaN%": isNaN,
    "%IteratorPrototype%": b && A ? A(A([][Symbol.iterator]())) : e,
    "%JSON%": typeof JSON == "object" ? JSON : e,
    "%Map%": typeof Map > "u" ? e : Map,
    "%MapIteratorPrototype%": typeof Map > "u" || !b || !A ? e : A((/* @__PURE__ */ new Map())[Symbol.iterator]()),
    "%Math%": Math,
    "%Number%": Number,
    "%Object%": t,
    "%Object.getOwnPropertyDescriptor%": I,
    "%parseFloat%": parseFloat,
    "%parseInt%": parseInt,
    "%Promise%": typeof Promise > "u" ? e : Promise,
    "%Proxy%": typeof Proxy > "u" ? e : Proxy,
    "%RangeError%": i,
    "%ReferenceError%": o,
    "%Reflect%": typeof Reflect > "u" ? e : Reflect,
    "%RegExp%": RegExp,
    "%Set%": typeof Set > "u" ? e : Set,
    "%SetIteratorPrototype%": typeof Set > "u" || !b || !A ? e : A((/* @__PURE__ */ new Set())[Symbol.iterator]()),
    "%SharedArrayBuffer%": typeof SharedArrayBuffer > "u" ? e : SharedArrayBuffer,
    "%String%": String,
    "%StringIteratorPrototype%": b && A ? A(""[Symbol.iterator]()) : e,
    "%Symbol%": b ? Symbol : e,
    "%SyntaxError%": a,
    "%ThrowTypeError%": h,
    "%TypedArray%": K,
    "%TypeError%": u,
    "%Uint8Array%": typeof Uint8Array > "u" ? e : Uint8Array,
    "%Uint8ClampedArray%": typeof Uint8ClampedArray > "u" ? e : Uint8ClampedArray,
    "%Uint16Array%": typeof Uint16Array > "u" ? e : Uint16Array,
    "%Uint32Array%": typeof Uint32Array > "u" ? e : Uint32Array,
    "%URIError%": s,
    "%WeakMap%": typeof WeakMap > "u" ? e : WeakMap,
    "%WeakRef%": typeof WeakRef > "u" ? e : WeakRef,
    "%WeakSet%": typeof WeakSet > "u" ? e : WeakSet,
    "%Function.prototype.call%": R,
    "%Function.prototype.apply%": C,
    "%Object.defineProperty%": E,
    "%Object.getPrototypeOf%": S,
    "%Math.abs%": c,
    "%Math.floor%": p,
    "%Math.max%": f,
    "%Math.min%": d,
    "%Math.pow%": P,
    "%Math.round%": _,
    "%Math.sign%": w,
    "%Reflect.getPrototypeOf%": $
  };
  if (A)
    try {
      null.error;
    } catch (M) {
      var B = A(A(M));
      T["%Error.prototype%"] = B;
    }
  var U = function M(q) {
    var L;
    if (q === "%AsyncFunction%")
      L = j("async function () {}");
    else if (q === "%GeneratorFunction%")
      L = j("function* () {}");
    else if (q === "%AsyncGeneratorFunction%")
      L = j("async function* () {}");
    else if (q === "%AsyncGenerator%") {
      var k = M("%AsyncGeneratorFunction%");
      k && (L = k.prototype);
    } else if (q === "%AsyncIteratorPrototype%") {
      var z = M("%AsyncGenerator%");
      z && A && (L = A(z.prototype));
    }
    return T[q] = L, L;
  }, X = {
    __proto__: null,
    "%ArrayBufferPrototype%": ["ArrayBuffer", "prototype"],
    "%ArrayPrototype%": ["Array", "prototype"],
    "%ArrayProto_entries%": ["Array", "prototype", "entries"],
    "%ArrayProto_forEach%": ["Array", "prototype", "forEach"],
    "%ArrayProto_keys%": ["Array", "prototype", "keys"],
    "%ArrayProto_values%": ["Array", "prototype", "values"],
    "%AsyncFunctionPrototype%": ["AsyncFunction", "prototype"],
    "%AsyncGenerator%": ["AsyncGeneratorFunction", "prototype"],
    "%AsyncGeneratorPrototype%": ["AsyncGeneratorFunction", "prototype", "prototype"],
    "%BooleanPrototype%": ["Boolean", "prototype"],
    "%DataViewPrototype%": ["DataView", "prototype"],
    "%DatePrototype%": ["Date", "prototype"],
    "%ErrorPrototype%": ["Error", "prototype"],
    "%EvalErrorPrototype%": ["EvalError", "prototype"],
    "%Float32ArrayPrototype%": ["Float32Array", "prototype"],
    "%Float64ArrayPrototype%": ["Float64Array", "prototype"],
    "%FunctionPrototype%": ["Function", "prototype"],
    "%Generator%": ["GeneratorFunction", "prototype"],
    "%GeneratorPrototype%": ["GeneratorFunction", "prototype", "prototype"],
    "%Int8ArrayPrototype%": ["Int8Array", "prototype"],
    "%Int16ArrayPrototype%": ["Int16Array", "prototype"],
    "%Int32ArrayPrototype%": ["Int32Array", "prototype"],
    "%JSONParse%": ["JSON", "parse"],
    "%JSONStringify%": ["JSON", "stringify"],
    "%MapPrototype%": ["Map", "prototype"],
    "%NumberPrototype%": ["Number", "prototype"],
    "%ObjectPrototype%": ["Object", "prototype"],
    "%ObjProto_toString%": ["Object", "prototype", "toString"],
    "%ObjProto_valueOf%": ["Object", "prototype", "valueOf"],
    "%PromisePrototype%": ["Promise", "prototype"],
    "%PromiseProto_then%": ["Promise", "prototype", "then"],
    "%Promise_all%": ["Promise", "all"],
    "%Promise_reject%": ["Promise", "reject"],
    "%Promise_resolve%": ["Promise", "resolve"],
    "%RangeErrorPrototype%": ["RangeError", "prototype"],
    "%ReferenceErrorPrototype%": ["ReferenceError", "prototype"],
    "%RegExpPrototype%": ["RegExp", "prototype"],
    "%SetPrototype%": ["Set", "prototype"],
    "%SharedArrayBufferPrototype%": ["SharedArrayBuffer", "prototype"],
    "%StringPrototype%": ["String", "prototype"],
    "%SymbolPrototype%": ["Symbol", "prototype"],
    "%SyntaxErrorPrototype%": ["SyntaxError", "prototype"],
    "%TypedArrayPrototype%": ["TypedArray", "prototype"],
    "%TypeErrorPrototype%": ["TypeError", "prototype"],
    "%Uint8ArrayPrototype%": ["Uint8Array", "prototype"],
    "%Uint8ClampedArrayPrototype%": ["Uint8ClampedArray", "prototype"],
    "%Uint16ArrayPrototype%": ["Uint16Array", "prototype"],
    "%Uint32ArrayPrototype%": ["Uint32Array", "prototype"],
    "%URIErrorPrototype%": ["URIError", "prototype"],
    "%WeakMapPrototype%": ["WeakMap", "prototype"],
    "%WeakSetPrototype%": ["WeakSet", "prototype"]
  }, re = qe(), se = /* @__PURE__ */ Xu(), J = re.call(R, Array.prototype.concat), Y = re.call(C, Array.prototype.splice), te = re.call(R, String.prototype.replace), l = re.call(R, String.prototype.slice), y = re.call(R, RegExp.prototype.exec), m = /[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g, D = /\\(\\)?/g, N = function(q) {
    var L = l(q, 0, 1), k = l(q, -1);
    if (L === "%" && k !== "%")
      throw new a("invalid intrinsic syntax, expected closing `%`");
    if (k === "%" && L !== "%")
      throw new a("invalid intrinsic syntax, expected opening `%`");
    var z = [];
    return te(q, m, function(Z, ne, H, ye) {
      z[z.length] = H ? te(ye, D, "$1") : ne || Z;
    }), z;
  }, G = function(q, L) {
    var k = q, z;
    if (se(X, k) && (z = X[k], k = "%" + z[0] + "%"), se(T, k)) {
      var Z = T[k];
      if (Z === V && (Z = U(k)), typeof Z > "u" && !L)
        throw new u("intrinsic " + q + " exists, but is not available. Please file an issue!");
      return {
        alias: z,
        name: k,
        value: Z
      };
    }
    throw new a("intrinsic " + q + " does not exist!");
  };
  return wr = function(q, L) {
    if (typeof q != "string" || q.length === 0)
      throw new u("intrinsic name must be a non-empty string");
    if (arguments.length > 1 && typeof L != "boolean")
      throw new u('"allowMissing" argument must be a boolean');
    if (y(/^%?[^%]*%?$/, q) === null)
      throw new a("`%` may not be present anywhere but at the beginning and end of the intrinsic name");
    var k = N(q), z = k.length > 0 ? k[0] : "", Z = G("%" + z + "%", L), ne = Z.name, H = Z.value, ye = !1, ve = Z.alias;
    ve && (z = ve[0], Y(k, J([0, 1], ve)));
    for (var v = 1, pe = !0; v < k.length; v += 1) {
      var ae = k[v], he = l(ae, 0, 1), F = l(ae, -1);
      if ((he === '"' || he === "'" || he === "`" || F === '"' || F === "'" || F === "`") && he !== F)
        throw new a("property names with quotes must have matching quotes");
      if ((ae === "constructor" || !pe) && (ye = !0), z += "." + ae, ne = "%" + z + "%", se(T, ne))
        H = T[ne];
      else if (H != null) {
        if (!(ae in H)) {
          if (!L)
            throw new u("base intrinsic for " + q + " exists, but the property is not available.");
          return;
        }
        if (I && v + 1 >= k.length) {
          var x = I(H, ae);
          pe = !!x, pe && "get" in x && !("originalValue" in x.get) ? H = x.get : H = H[ae];
        } else
          pe = se(H, ae), H = H[ae];
        pe && !ye && (T[ne] = H);
      }
    }
    return H;
  }, wr;
}
var jr, oo;
function we() {
  if (oo) return jr;
  oo = 1;
  var e = /* @__PURE__ */ Yu(), t = fi(), r = t([e("%String.prototype.indexOf%")]);
  return jr = function(i, o) {
    var a = (
      /** @type {Parameters<typeof callBindBasic>[0][0]} */
      e(i, !!o)
    );
    return typeof a == "function" && r(i, ".prototype.") > -1 ? t([a]) : a;
  }, jr;
}
var Er, ao;
function tc() {
  if (ao) return Er;
  ao = 1;
  var e = Le()(), t = /* @__PURE__ */ we(), r = t("Object.prototype.toString"), n = function(u) {
    return e && u && typeof u == "object" && Symbol.toStringTag in u ? !1 : r(u) === "[object Arguments]";
  }, i = function(u) {
    return n(u) ? !0 : u !== null && typeof u == "object" && "length" in u && typeof u.length == "number" && u.length >= 0 && r(u) !== "[object Array]" && "callee" in u && r(u.callee) === "[object Function]";
  }, o = function() {
    return n(arguments);
  }();
  return n.isLegacyArguments = i, Er = o ? n : i, Er;
}
var Ir, uo;
function nc() {
  if (uo) return Ir;
  uo = 1;
  var e = /* @__PURE__ */ we(), t = Le()(), r = /* @__PURE__ */ Xu(), n = /* @__PURE__ */ Ae(), i;
  if (t) {
    var o = e("RegExp.prototype.exec"), a = {}, u = function() {
      throw a;
    }, s = {
      toString: u,
      valueOf: u
    };
    typeof Symbol.toPrimitive == "symbol" && (s[Symbol.toPrimitive] = u), i = function(d) {
      if (!d || typeof d != "object")
        return !1;
      var P = (
        /** @type {NonNullable<typeof gOPD>} */
        n(
          /** @type {{ lastIndex?: unknown }} */
          d,
          "lastIndex"
        )
      ), _ = P && r(P, "value");
      if (!_)
        return !1;
      try {
        o(
          d,
          /** @type {string} */
          /** @type {unknown} */
          s
        );
      } catch (w) {
        return w === a;
      }
    };
  } else {
    var c = e("Object.prototype.toString"), p = "[object RegExp]";
    i = function(d) {
      return !d || typeof d != "object" && typeof d != "function" ? !1 : c(d) === p;
    };
  }
  return Ir = i, Ir;
}
var Cr, so;
function ic() {
  if (so) return Cr;
  so = 1;
  var e = /* @__PURE__ */ we(), t = nc(), r = e("RegExp.prototype.exec"), n = /* @__PURE__ */ Re();
  return Cr = function(o) {
    if (!t(o))
      throw new n("`regex` must be a RegExp");
    return function(u) {
      return r(o, u) !== null;
    };
  }, Cr;
}
var Fr, co;
function oc() {
  if (co) return Fr;
  co = 1;
  var e = /* @__PURE__ */ we(), t = /* @__PURE__ */ ic(), r = t(/^\s*(?:function)?\*/), n = Le()(), i = Ku(), o = e("Object.prototype.toString"), a = e("Function.prototype.toString"), u = function() {
    if (!n)
      return !1;
    try {
      return Function("return function*() {}")();
    } catch {
    }
  }, s;
  return Fr = function(p) {
    if (typeof p != "function")
      return !1;
    if (r(a(p)))
      return !0;
    if (!n) {
      var f = o(p);
      return f === "[object GeneratorFunction]";
    }
    if (!i)
      return !1;
    if (typeof s > "u") {
      var d = u();
      s = d ? (
        /** @type {GeneratorFunctionConstructor} */
        i(d)
      ) : !1;
    }
    return i(p) === s;
  }, Fr;
}
var Br, fo;
function ac() {
  if (fo) return Br;
  fo = 1;
  var e = Function.prototype.toString, t = typeof Reflect == "object" && Reflect !== null && Reflect.apply, r, n;
  if (typeof t == "function" && typeof Object.defineProperty == "function")
    try {
      r = Object.defineProperty({}, "length", {
        get: function() {
          throw n;
        }
      }), n = {}, t(function() {
        throw 42;
      }, null, r);
    } catch (I) {
      I !== n && (t = null);
    }
  else
    t = null;
  var i = /^\s*class\b/, o = function(E) {
    try {
      var g = e.call(E);
      return i.test(g);
    } catch {
      return !1;
    }
  }, a = function(E) {
    try {
      return o(E) ? !1 : (e.call(E), !0);
    } catch {
      return !1;
    }
  }, u = Object.prototype.toString, s = "[object Object]", c = "[object Function]", p = "[object GeneratorFunction]", f = "[object HTMLAllCollection]", d = "[object HTML document.all class]", P = "[object HTMLCollection]", _ = typeof Symbol == "function" && !!Symbol.toStringTag, w = !(0 in [,]), O = function() {
    return !1;
  };
  if (typeof document == "object") {
    var j = document.all;
    u.call(j) === u.call(document.all) && (O = function(E) {
      if ((w || !E) && (typeof E > "u" || typeof E == "object"))
        try {
          var g = u.call(E);
          return (g === f || g === d || g === P || g === s) && E("") == null;
        } catch {
        }
      return !1;
    });
  }
  return Br = t ? function(E) {
    if (O(E))
      return !0;
    if (!E || typeof E != "function" && typeof E != "object")
      return !1;
    try {
      t(E, null, r);
    } catch (g) {
      if (g !== n)
        return !1;
    }
    return !o(E) && a(E);
  } : function(E) {
    if (O(E))
      return !0;
    if (!E || typeof E != "function" && typeof E != "object")
      return !1;
    if (_)
      return a(E);
    if (o(E))
      return !1;
    var g = u.call(E);
    return g !== c && g !== p && !/^\[object HTML/.test(g) ? !1 : a(E);
  }, Br;
}
var Mr, po;
function uc() {
  if (po) return Mr;
  po = 1;
  var e = ac(), t = Object.prototype.toString, r = Object.prototype.hasOwnProperty, n = function(s, c, p) {
    for (var f = 0, d = s.length; f < d; f++)
      r.call(s, f) && (p == null ? c(s[f], f, s) : c.call(p, s[f], f, s));
  }, i = function(s, c, p) {
    for (var f = 0, d = s.length; f < d; f++)
      p == null ? c(s.charAt(f), f, s) : c.call(p, s.charAt(f), f, s);
  }, o = function(s, c, p) {
    for (var f in s)
      r.call(s, f) && (p == null ? c(s[f], f, s) : c.call(p, s[f], f, s));
  };
  function a(u) {
    return t.call(u) === "[object Array]";
  }
  return Mr = function(s, c, p) {
    if (!e(c))
      throw new TypeError("iterator must be a function");
    var f;
    arguments.length >= 3 && (f = p), a(s) ? n(s, c, f) : typeof s == "string" ? i(s, c, f) : o(s, c, f);
  }, Mr;
}
var Lr, lo;
function sc() {
  return lo || (lo = 1, Lr = [
    "Float16Array",
    "Float32Array",
    "Float64Array",
    "Int8Array",
    "Int16Array",
    "Int32Array",
    "Uint8Array",
    "Uint8ClampedArray",
    "Uint16Array",
    "Uint32Array",
    "BigInt64Array",
    "BigUint64Array"
  ]), Lr;
}
var Dr, yo;
function cc() {
  if (yo) return Dr;
  yo = 1;
  var e = /* @__PURE__ */ sc(), t = typeof globalThis > "u" ? _e : globalThis;
  return Dr = function() {
    for (var n = [], i = 0; i < e.length; i++)
      typeof t[e[i]] == "function" && (n[n.length] = e[i]);
    return n;
  }, Dr;
}
var Nr = { exports: {} }, $r, vo;
function fc() {
  if (vo) return $r;
  vo = 1;
  var e = /* @__PURE__ */ De(), t = /* @__PURE__ */ Ju(), r = /* @__PURE__ */ Re(), n = /* @__PURE__ */ Ae();
  return $r = function(o, a, u) {
    if (!o || typeof o != "object" && typeof o != "function")
      throw new r("`obj` must be an object or a function`");
    if (typeof a != "string" && typeof a != "symbol")
      throw new r("`property` must be a string or a symbol`");
    if (arguments.length > 3 && typeof arguments[3] != "boolean" && arguments[3] !== null)
      throw new r("`nonEnumerable`, if provided, must be a boolean or null");
    if (arguments.length > 4 && typeof arguments[4] != "boolean" && arguments[4] !== null)
      throw new r("`nonWritable`, if provided, must be a boolean or null");
    if (arguments.length > 5 && typeof arguments[5] != "boolean" && arguments[5] !== null)
      throw new r("`nonConfigurable`, if provided, must be a boolean or null");
    if (arguments.length > 6 && typeof arguments[6] != "boolean")
      throw new r("`loose`, if provided, must be a boolean");
    var s = arguments.length > 3 ? arguments[3] : null, c = arguments.length > 4 ? arguments[4] : null, p = arguments.length > 5 ? arguments[5] : null, f = arguments.length > 6 ? arguments[6] : !1, d = !!n && n(o, a);
    if (e)
      e(o, a, {
        configurable: p === null && d ? d.configurable : !p,
        enumerable: s === null && d ? d.enumerable : !s,
        value: u,
        writable: c === null && d ? d.writable : !c
      });
    else if (f || !s && !c && !p)
      o[a] = u;
    else
      throw new t("This environment does not support defining a property as non-configurable, non-writable, or non-enumerable.");
  }, $r;
}
var Ur, go;
function pc() {
  if (go) return Ur;
  go = 1;
  var e = /* @__PURE__ */ De(), t = function() {
    return !!e;
  };
  return t.hasArrayLengthDefineBug = function() {
    if (!e)
      return null;
    try {
      return e([], "length", { value: 1 }).length !== 1;
    } catch {
      return !0;
    }
  }, Ur = t, Ur;
}
var kr, ho;
function lc() {
  if (ho) return kr;
  ho = 1;
  var e = /* @__PURE__ */ Yu(), t = /* @__PURE__ */ fc(), r = /* @__PURE__ */ pc()(), n = /* @__PURE__ */ Ae(), i = /* @__PURE__ */ Re(), o = e("%Math.floor%");
  return kr = function(u, s) {
    if (typeof u != "function")
      throw new i("`fn` is not a function");
    if (typeof s != "number" || s < 0 || s > 4294967295 || o(s) !== s)
      throw new i("`length` must be a positive 32-bit integer");
    var c = arguments.length > 2 && !!arguments[2], p = !0, f = !0;
    if ("length" in u && n) {
      var d = n(u, "length");
      d && !d.configurable && (p = !1), d && !d.writable && (f = !1);
    }
    return (p || f || !c) && (r ? t(
      /** @type {Parameters<define>[0]} */
      u,
      "length",
      s,
      !0,
      !0
    ) : t(
      /** @type {Parameters<define>[0]} */
      u,
      "length",
      s
    )), u;
  }, kr;
}
var xr, mo;
function dc() {
  if (mo) return xr;
  mo = 1;
  var e = qe(), t = ci(), r = Hu();
  return xr = function() {
    return r(e, t, arguments);
  }, xr;
}
var _o;
function yc() {
  return _o || (_o = 1, function(e) {
    var t = /* @__PURE__ */ lc(), r = /* @__PURE__ */ De(), n = fi(), i = dc();
    e.exports = function(a) {
      var u = n(arguments), s = a.length - (arguments.length - 1);
      return t(
        u,
        1 + (s > 0 ? s : 0),
        !0
      );
    }, r ? r(e.exports, "apply", { value: i }) : e.exports.apply = i;
  }(Nr)), Nr.exports;
}
var Gr, bo;
function Zu() {
  if (bo) return Gr;
  bo = 1;
  var e = uc(), t = /* @__PURE__ */ cc(), r = yc(), n = /* @__PURE__ */ we(), i = /* @__PURE__ */ Ae(), o = n("Object.prototype.toString"), a = Le()(), u = typeof globalThis > "u" ? _e : globalThis, s = t(), c = n("String.prototype.slice"), p = Object.getPrototypeOf, f = n("Array.prototype.indexOf", !0) || function(O, j) {
    for (var I = 0; I < O.length; I += 1)
      if (O[I] === j)
        return I;
    return -1;
  }, d = { __proto__: null };
  a && i && p ? e(s, function(w) {
    var O = new u[w]();
    if (Symbol.toStringTag in O) {
      var j = p(O), I = i(j, Symbol.toStringTag);
      if (!I) {
        var E = p(j);
        I = i(E, Symbol.toStringTag);
      }
      d["$" + w] = r(I.get);
    }
  }) : e(s, function(w) {
    var O = new u[w](), j = O.slice || O.set;
    j && (d["$" + w] = r(j));
  });
  var P = function(O) {
    var j = !1;
    return e(
      // eslint-disable-next-line no-extra-parens
      /** @type {Record<`\$${TypedArrayName}`, Getter>} */
      /** @type {any} */
      d,
      /** @type {(getter: Getter, name: `\$${import('.').TypedArrayName}`) => void} */
      function(I, E) {
        if (!j)
          try {
            "$" + I(O) === E && (j = c(E, 1));
          } catch {
          }
      }
    ), j;
  }, _ = function(O) {
    var j = !1;
    return e(
      // eslint-disable-next-line no-extra-parens
      /** @type {Record<`\$${TypedArrayName}`, Getter>} */
      /** @type {any} */
      d,
      /** @type {(getter: typeof cache, name: `\$${import('.').TypedArrayName}`) => void} */
      function(I, E) {
        if (!j)
          try {
            I(O), j = c(E, 1);
          } catch {
          }
      }
    ), j;
  };
  return Gr = function(O) {
    if (!O || typeof O != "object")
      return !1;
    if (!a) {
      var j = c(o(O), 8, -1);
      return f(s, j) > -1 ? j : j !== "Object" ? !1 : _(O);
    }
    return i ? P(O) : null;
  }, Gr;
}
var Vr, Ao;
function vc() {
  if (Ao) return Vr;
  Ao = 1;
  var e = /* @__PURE__ */ Zu();
  return Vr = function(r) {
    return !!e(r);
  }, Vr;
}
var Po;
function gc() {
  return Po || (Po = 1, function(e) {
    var t = /* @__PURE__ */ tc(), r = oc(), n = /* @__PURE__ */ Zu(), i = /* @__PURE__ */ vc();
    function o(v) {
      return v.call.bind(v);
    }
    var a = typeof BigInt < "u", u = typeof Symbol < "u", s = o(Object.prototype.toString), c = o(Number.prototype.valueOf), p = o(String.prototype.valueOf), f = o(Boolean.prototype.valueOf);
    if (a)
      var d = o(BigInt.prototype.valueOf);
    if (u)
      var P = o(Symbol.prototype.valueOf);
    function _(v, pe) {
      if (typeof v != "object")
        return !1;
      try {
        return pe(v), !0;
      } catch {
        return !1;
      }
    }
    e.isArgumentsObject = t, e.isGeneratorFunction = r, e.isTypedArray = i;
    function w(v) {
      return typeof Promise < "u" && v instanceof Promise || v !== null && typeof v == "object" && typeof v.then == "function" && typeof v.catch == "function";
    }
    e.isPromise = w;
    function O(v) {
      return typeof ArrayBuffer < "u" && ArrayBuffer.isView ? ArrayBuffer.isView(v) : i(v) || l(v);
    }
    e.isArrayBufferView = O;
    function j(v) {
      return n(v) === "Uint8Array";
    }
    e.isUint8Array = j;
    function I(v) {
      return n(v) === "Uint8ClampedArray";
    }
    e.isUint8ClampedArray = I;
    function E(v) {
      return n(v) === "Uint16Array";
    }
    e.isUint16Array = E;
    function g(v) {
      return n(v) === "Uint32Array";
    }
    e.isUint32Array = g;
    function h(v) {
      return n(v) === "Int8Array";
    }
    e.isInt8Array = h;
    function b(v) {
      return n(v) === "Int16Array";
    }
    e.isInt16Array = b;
    function A(v) {
      return n(v) === "Int32Array";
    }
    e.isInt32Array = A;
    function S(v) {
      return n(v) === "Float32Array";
    }
    e.isFloat32Array = S;
    function $(v) {
      return n(v) === "Float64Array";
    }
    e.isFloat64Array = $;
    function C(v) {
      return n(v) === "BigInt64Array";
    }
    e.isBigInt64Array = C;
    function R(v) {
      return n(v) === "BigUint64Array";
    }
    e.isBigUint64Array = R;
    function V(v) {
      return s(v) === "[object Map]";
    }
    V.working = typeof Map < "u" && V(/* @__PURE__ */ new Map());
    function K(v) {
      return typeof Map > "u" ? !1 : V.working ? V(v) : v instanceof Map;
    }
    e.isMap = K;
    function T(v) {
      return s(v) === "[object Set]";
    }
    T.working = typeof Set < "u" && T(/* @__PURE__ */ new Set());
    function B(v) {
      return typeof Set > "u" ? !1 : T.working ? T(v) : v instanceof Set;
    }
    e.isSet = B;
    function U(v) {
      return s(v) === "[object WeakMap]";
    }
    U.working = typeof WeakMap < "u" && U(/* @__PURE__ */ new WeakMap());
    function X(v) {
      return typeof WeakMap > "u" ? !1 : U.working ? U(v) : v instanceof WeakMap;
    }
    e.isWeakMap = X;
    function re(v) {
      return s(v) === "[object WeakSet]";
    }
    re.working = typeof WeakSet < "u" && re(/* @__PURE__ */ new WeakSet());
    function se(v) {
      return re(v);
    }
    e.isWeakSet = se;
    function J(v) {
      return s(v) === "[object ArrayBuffer]";
    }
    J.working = typeof ArrayBuffer < "u" && J(new ArrayBuffer());
    function Y(v) {
      return typeof ArrayBuffer > "u" ? !1 : J.working ? J(v) : v instanceof ArrayBuffer;
    }
    e.isArrayBuffer = Y;
    function te(v) {
      return s(v) === "[object DataView]";
    }
    te.working = typeof ArrayBuffer < "u" && typeof DataView < "u" && te(new DataView(new ArrayBuffer(1), 0, 1));
    function l(v) {
      return typeof DataView > "u" ? !1 : te.working ? te(v) : v instanceof DataView;
    }
    e.isDataView = l;
    var y = typeof SharedArrayBuffer < "u" ? SharedArrayBuffer : void 0;
    function m(v) {
      return s(v) === "[object SharedArrayBuffer]";
    }
    function D(v) {
      return typeof y > "u" ? !1 : (typeof m.working > "u" && (m.working = m(new y())), m.working ? m(v) : v instanceof y);
    }
    e.isSharedArrayBuffer = D;
    function N(v) {
      return s(v) === "[object AsyncFunction]";
    }
    e.isAsyncFunction = N;
    function G(v) {
      return s(v) === "[object Map Iterator]";
    }
    e.isMapIterator = G;
    function M(v) {
      return s(v) === "[object Set Iterator]";
    }
    e.isSetIterator = M;
    function q(v) {
      return s(v) === "[object Generator]";
    }
    e.isGeneratorObject = q;
    function L(v) {
      return s(v) === "[object WebAssembly.Module]";
    }
    e.isWebAssemblyCompiledModule = L;
    function k(v) {
      return _(v, c);
    }
    e.isNumberObject = k;
    function z(v) {
      return _(v, p);
    }
    e.isStringObject = z;
    function Z(v) {
      return _(v, f);
    }
    e.isBooleanObject = Z;
    function ne(v) {
      return a && _(v, d);
    }
    e.isBigIntObject = ne;
    function H(v) {
      return u && _(v, P);
    }
    e.isSymbolObject = H;
    function ye(v) {
      return k(v) || z(v) || Z(v) || ne(v) || H(v);
    }
    e.isBoxedPrimitive = ye;
    function ve(v) {
      return typeof Uint8Array < "u" && (Y(v) || D(v));
    }
    e.isAnyArrayBuffer = ve, ["isProxy", "isExternal", "isModuleNamespaceObject"].forEach(function(v) {
      Object.defineProperty(e, v, {
        enumerable: !1,
        value: function() {
          throw new Error(v + " is not supported in userland");
        }
      });
    });
  }(ze)), ze;
}
var Jr, Oo;
function hc() {
  return Oo || (Oo = 1, Jr = function(t) {
    return t && typeof t == "object" && typeof t.copy == "function" && typeof t.fill == "function" && typeof t.readUInt8 == "function";
  }), Jr;
}
var Ee = { exports: {} }, To;
function mc() {
  return To || (To = 1, typeof Object.create == "function" ? Ee.exports = function(t, r) {
    r && (t.super_ = r, t.prototype = Object.create(r.prototype, {
      constructor: {
        value: t,
        enumerable: !1,
        writable: !0,
        configurable: !0
      }
    }));
  } : Ee.exports = function(t, r) {
    if (r) {
      t.super_ = r;
      var n = function() {
      };
      n.prototype = r.prototype, t.prototype = new n(), t.prototype.constructor = t;
    }
  }), Ee.exports;
}
var So;
function _c() {
  return So || (So = 1, function(e) {
    var t = {}, r = Object.getOwnPropertyDescriptors || function(y) {
      for (var m = Object.keys(y), D = {}, N = 0; N < m.length; N++)
        D[m[N]] = Object.getOwnPropertyDescriptor(y, m[N]);
      return D;
    }, n = /%[sdj%]/g;
    e.format = function(l) {
      if (!b(l)) {
        for (var y = [], m = 0; m < arguments.length; m++)
          y.push(u(arguments[m]));
        return y.join(" ");
      }
      for (var m = 1, D = arguments, N = D.length, G = String(l).replace(n, function(q) {
        if (q === "%%") return "%";
        if (m >= N) return q;
        switch (q) {
          case "%s":
            return String(D[m++]);
          case "%d":
            return Number(D[m++]);
          case "%j":
            try {
              return JSON.stringify(D[m++]);
            } catch {
              return "[Circular]";
            }
          default:
            return q;
        }
      }), M = D[m]; m < N; M = D[++m])
        E(M) || !C(M) ? G += " " + M : G += " " + u(M);
      return G;
    }, e.deprecate = function(l, y) {
      if (typeof process < "u" && process.noDeprecation === !0)
        return l;
      if (typeof process > "u")
        return function() {
          return e.deprecate(l, y).apply(this, arguments);
        };
      var m = !1;
      function D() {
        if (!m) {
          if (process.throwDeprecation)
            throw new Error(y);
          process.traceDeprecation ? console.trace(y) : console.error(y), m = !0;
        }
        return l.apply(this, arguments);
      }
      return D;
    };
    var i = {}, o = /^$/;
    if (t.NODE_DEBUG) {
      var a = t.NODE_DEBUG;
      a = a.replace(/[|\\{}()[\]^$+?.]/g, "\\$&").replace(/\*/g, ".*").replace(/,/g, "$|^").toUpperCase(), o = new RegExp("^" + a + "$", "i");
    }
    e.debuglog = function(l) {
      if (l = l.toUpperCase(), !i[l])
        if (o.test(l)) {
          var y = process.pid;
          i[l] = function() {
            var m = e.format.apply(e, arguments);
            console.error("%s %d: %s", l, y, m);
          };
        } else
          i[l] = function() {
          };
      return i[l];
    };
    function u(l, y) {
      var m = {
        seen: [],
        stylize: c
      };
      return arguments.length >= 3 && (m.depth = arguments[2]), arguments.length >= 4 && (m.colors = arguments[3]), I(y) ? m.showHidden = y : y && e._extend(m, y), S(m.showHidden) && (m.showHidden = !1), S(m.depth) && (m.depth = 2), S(m.colors) && (m.colors = !1), S(m.customInspect) && (m.customInspect = !0), m.colors && (m.stylize = s), f(m, l, m.depth);
    }
    e.inspect = u, u.colors = {
      bold: [1, 22],
      italic: [3, 23],
      underline: [4, 24],
      inverse: [7, 27],
      white: [37, 39],
      grey: [90, 39],
      black: [30, 39],
      blue: [34, 39],
      cyan: [36, 39],
      green: [32, 39],
      magenta: [35, 39],
      red: [31, 39],
      yellow: [33, 39]
    }, u.styles = {
      special: "cyan",
      number: "yellow",
      boolean: "yellow",
      undefined: "grey",
      null: "bold",
      string: "green",
      date: "magenta",
      // "name": intentionally not styling
      regexp: "red"
    };
    function s(l, y) {
      var m = u.styles[y];
      return m ? "\x1B[" + u.colors[m][0] + "m" + l + "\x1B[" + u.colors[m][1] + "m" : l;
    }
    function c(l, y) {
      return l;
    }
    function p(l) {
      var y = {};
      return l.forEach(function(m, D) {
        y[m] = !0;
      }), y;
    }
    function f(l, y, m) {
      if (l.customInspect && y && K(y.inspect) && // Filter out the util module, it's inspect function is special
      y.inspect !== e.inspect && // Also filter out any prototype objects using the circular check.
      !(y.constructor && y.constructor.prototype === y)) {
        var D = y.inspect(m, l);
        return b(D) || (D = f(l, D, m)), D;
      }
      var N = d(l, y);
      if (N)
        return N;
      var G = Object.keys(y), M = p(G);
      if (l.showHidden && (G = Object.getOwnPropertyNames(y)), V(y) && (G.indexOf("message") >= 0 || G.indexOf("description") >= 0))
        return P(y);
      if (G.length === 0) {
        if (K(y)) {
          var q = y.name ? ": " + y.name : "";
          return l.stylize("[Function" + q + "]", "special");
        }
        if ($(y))
          return l.stylize(RegExp.prototype.toString.call(y), "regexp");
        if (R(y))
          return l.stylize(Date.prototype.toString.call(y), "date");
        if (V(y))
          return P(y);
      }
      var L = "", k = !1, z = ["{", "}"];
      if (j(y) && (k = !0, z = ["[", "]"]), K(y)) {
        var Z = y.name ? ": " + y.name : "";
        L = " [Function" + Z + "]";
      }
      if ($(y) && (L = " " + RegExp.prototype.toString.call(y)), R(y) && (L = " " + Date.prototype.toUTCString.call(y)), V(y) && (L = " " + P(y)), G.length === 0 && (!k || y.length == 0))
        return z[0] + L + z[1];
      if (m < 0)
        return $(y) ? l.stylize(RegExp.prototype.toString.call(y), "regexp") : l.stylize("[Object]", "special");
      l.seen.push(y);
      var ne;
      return k ? ne = _(l, y, m, M, G) : ne = G.map(function(H) {
        return w(l, y, m, M, H, k);
      }), l.seen.pop(), O(ne, L, z);
    }
    function d(l, y) {
      if (S(y))
        return l.stylize("undefined", "undefined");
      if (b(y)) {
        var m = "'" + JSON.stringify(y).replace(/^"|"$/g, "").replace(/'/g, "\\'").replace(/\\"/g, '"') + "'";
        return l.stylize(m, "string");
      }
      if (h(y))
        return l.stylize("" + y, "number");
      if (I(y))
        return l.stylize("" + y, "boolean");
      if (E(y))
        return l.stylize("null", "null");
    }
    function P(l) {
      return "[" + Error.prototype.toString.call(l) + "]";
    }
    function _(l, y, m, D, N) {
      for (var G = [], M = 0, q = y.length; M < q; ++M)
        se(y, String(M)) ? G.push(w(
          l,
          y,
          m,
          D,
          String(M),
          !0
        )) : G.push("");
      return N.forEach(function(L) {
        L.match(/^\d+$/) || G.push(w(
          l,
          y,
          m,
          D,
          L,
          !0
        ));
      }), G;
    }
    function w(l, y, m, D, N, G) {
      var M, q, L;
      if (L = Object.getOwnPropertyDescriptor(y, N) || { value: y[N] }, L.get ? L.set ? q = l.stylize("[Getter/Setter]", "special") : q = l.stylize("[Getter]", "special") : L.set && (q = l.stylize("[Setter]", "special")), se(D, N) || (M = "[" + N + "]"), q || (l.seen.indexOf(L.value) < 0 ? (E(m) ? q = f(l, L.value, null) : q = f(l, L.value, m - 1), q.indexOf(`
`) > -1 && (G ? q = q.split(`
`).map(function(k) {
        return "  " + k;
      }).join(`
`).slice(2) : q = `
` + q.split(`
`).map(function(k) {
        return "   " + k;
      }).join(`
`))) : q = l.stylize("[Circular]", "special")), S(M)) {
        if (G && N.match(/^\d+$/))
          return q;
        M = JSON.stringify("" + N), M.match(/^"([a-zA-Z_][a-zA-Z_0-9]*)"$/) ? (M = M.slice(1, -1), M = l.stylize(M, "name")) : (M = M.replace(/'/g, "\\'").replace(/\\"/g, '"').replace(/(^"|"$)/g, "'"), M = l.stylize(M, "string"));
      }
      return M + ": " + q;
    }
    function O(l, y, m) {
      var D = l.reduce(function(N, G) {
        return G.indexOf(`
`) >= 0, N + G.replace(/\u001b\[\d\d?m/g, "").length + 1;
      }, 0);
      return D > 60 ? m[0] + (y === "" ? "" : y + `
 `) + " " + l.join(`,
  `) + " " + m[1] : m[0] + y + " " + l.join(", ") + " " + m[1];
    }
    e.types = gc();
    function j(l) {
      return Array.isArray(l);
    }
    e.isArray = j;
    function I(l) {
      return typeof l == "boolean";
    }
    e.isBoolean = I;
    function E(l) {
      return l === null;
    }
    e.isNull = E;
    function g(l) {
      return l == null;
    }
    e.isNullOrUndefined = g;
    function h(l) {
      return typeof l == "number";
    }
    e.isNumber = h;
    function b(l) {
      return typeof l == "string";
    }
    e.isString = b;
    function A(l) {
      return typeof l == "symbol";
    }
    e.isSymbol = A;
    function S(l) {
      return l === void 0;
    }
    e.isUndefined = S;
    function $(l) {
      return C(l) && B(l) === "[object RegExp]";
    }
    e.isRegExp = $, e.types.isRegExp = $;
    function C(l) {
      return typeof l == "object" && l !== null;
    }
    e.isObject = C;
    function R(l) {
      return C(l) && B(l) === "[object Date]";
    }
    e.isDate = R, e.types.isDate = R;
    function V(l) {
      return C(l) && (B(l) === "[object Error]" || l instanceof Error);
    }
    e.isError = V, e.types.isNativeError = V;
    function K(l) {
      return typeof l == "function";
    }
    e.isFunction = K;
    function T(l) {
      return l === null || typeof l == "boolean" || typeof l == "number" || typeof l == "string" || typeof l == "symbol" || // ES6 symbol
      typeof l > "u";
    }
    e.isPrimitive = T, e.isBuffer = hc();
    function B(l) {
      return Object.prototype.toString.call(l);
    }
    function U(l) {
      return l < 10 ? "0" + l.toString(10) : l.toString(10);
    }
    var X = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec"
    ];
    function re() {
      var l = /* @__PURE__ */ new Date(), y = [
        U(l.getHours()),
        U(l.getMinutes()),
        U(l.getSeconds())
      ].join(":");
      return [l.getDate(), X[l.getMonth()], y].join(" ");
    }
    e.log = function() {
      console.log("%s - %s", re(), e.format.apply(e, arguments));
    }, e.inherits = mc(), e._extend = function(l, y) {
      if (!y || !C(y)) return l;
      for (var m = Object.keys(y), D = m.length; D--; )
        l[m[D]] = y[m[D]];
      return l;
    };
    function se(l, y) {
      return Object.prototype.hasOwnProperty.call(l, y);
    }
    var J = typeof Symbol < "u" ? Symbol("util.promisify.custom") : void 0;
    e.promisify = function(y) {
      if (typeof y != "function")
        throw new TypeError('The "original" argument must be of type Function');
      if (J && y[J]) {
        var m = y[J];
        if (typeof m != "function")
          throw new TypeError('The "util.promisify.custom" argument must be of type Function');
        return Object.defineProperty(m, J, {
          value: m,
          enumerable: !1,
          writable: !1,
          configurable: !0
        }), m;
      }
      function m() {
        for (var D, N, G = new Promise(function(L, k) {
          D = L, N = k;
        }), M = [], q = 0; q < arguments.length; q++)
          M.push(arguments[q]);
        M.push(function(L, k) {
          L ? N(L) : D(k);
        });
        try {
          y.apply(this, M);
        } catch (L) {
          N(L);
        }
        return G;
      }
      return Object.setPrototypeOf(m, Object.getPrototypeOf(y)), J && Object.defineProperty(m, J, {
        value: m,
        enumerable: !1,
        writable: !1,
        configurable: !0
      }), Object.defineProperties(
        m,
        r(y)
      );
    }, e.promisify.custom = J;
    function Y(l, y) {
      if (!l) {
        var m = new Error("Promise was rejected with a falsy value");
        m.reason = l, l = m;
      }
      return y(l);
    }
    function te(l) {
      if (typeof l != "function")
        throw new TypeError('The "original" argument must be of type Function');
      function y() {
        for (var m = [], D = 0; D < arguments.length; D++)
          m.push(arguments[D]);
        var N = m.pop();
        if (typeof N != "function")
          throw new TypeError("The last argument must be of type Function");
        var G = this, M = function() {
          return N.apply(G, arguments);
        };
        l.apply(this, m).then(
          function(q) {
            process.nextTick(M.bind(null, null, q));
          },
          function(q) {
            process.nextTick(Y.bind(null, q, M));
          }
        );
      }
      return Object.setPrototypeOf(y, Object.getPrototypeOf(l)), Object.defineProperties(
        y,
        r(l)
      ), y;
    }
    e.callbackify = te;
  }(We)), We;
}
var Wr, Ro;
function bc() {
  return Ro || (Ro = 1, Wr = function(t) {
    return t ? t instanceof Array || Array.isArray(t) || t.length >= 0 && t.splice instanceof Function : !1;
  }), Wr;
}
var zr, qo;
function Qu() {
  if (qo) return zr;
  qo = 1;
  var e = _c(), t = bc(), r = function(i, o) {
    (!i || i.constructor !== String) && (o = i || {}, i = Error.name);
    var a = function u(s) {
      if (!this)
        return new u(s);
      s = s instanceof Error ? s.message : s || this.message, Error.call(this, s), Error.captureStackTrace(this, a), this.name = i, Object.defineProperty(this, "message", {
        configurable: !0,
        enumerable: !1,
        get: function() {
          var P = s.split(/\r?\n/g);
          for (var _ in o)
            if (o.hasOwnProperty(_)) {
              var w = o[_];
              "message" in w && (P = w.message(this[_], P) || P, t(P) || (P = [P]));
            }
          return P.join(`
`);
        },
        set: function(P) {
          s = P;
        }
      });
      var c = null, p = Object.getOwnPropertyDescriptor(this, "stack"), f = p.get, d = p.value;
      delete p.value, delete p.writable, p.set = function(P) {
        c = P;
      }, p.get = function() {
        var P = (c || (f ? f.call(this) : d)).split(/\r?\n+/g);
        c || (P[0] = this.name + ": " + this.message);
        var _ = 1;
        for (var w in o)
          if (o.hasOwnProperty(w)) {
            var O = o[w];
            if ("line" in O) {
              var j = O.line(this[w]);
              j && P.splice(_++, 0, "    " + j);
            }
            "stack" in O && O.stack(this[w], P);
          }
        return P.join(`
`);
      }, Object.defineProperty(this, "stack", p);
    };
    return Object.setPrototypeOf ? (Object.setPrototypeOf(a.prototype, Error.prototype), Object.setPrototypeOf(a, Error)) : e.inherits(a, Error), a;
  };
  return r.append = function(n, i) {
    return {
      message: function(o, a) {
        return o = o || i, o && (a[0] += " " + n.replace("%s", o.toString())), a;
      }
    };
  }, r.line = function(n, i) {
    return {
      line: function(o) {
        return o = o || i, o ? n.replace("%s", o.toString()) : null;
      }
    };
  }, zr = r, zr;
}
var Ac = Qu();
const Pc = /* @__PURE__ */ ui(Ac), es = Pc("error"), Oc = { class: "ui-predicate__options" }, Tc = { class: "ui-predicate__option" }, Sc = { class: "ui-predicate__option" }, rs = {
  __name: "ui-predicate-options",
  props: {
    predicate: {
      type: Object,
      required: !0
    }
  },
  setup(e) {
    const t = ie("remove"), r = ie("add"), n = ie("getAddCompoundMode"), i = ie("UITypes"), o = ie("getUIComponent"), a = Fs(() => n());
    return (u, s) => (W(), Q("div", Oc, [
      fe("div", Tc, [
        (W(), ge(Te(ue(o)(ue(i).PREDICATE_REMOVE)), {
          predicate: e.predicate,
          disabled: e.predicate.$canBeRemoved === !1,
          onClick: s[0] || (s[0] = (c) => ue(t)(e.predicate))
        }, null, 8, ["predicate", "disabled"]))
      ]),
      fe("div", Sc, [
        (W(), ge(Te(ue(o)(ue(i).PREDICATE_ADD)), {
          predicate: e.predicate,
          "is-in-add-compound-mode": a.value,
          onClick: s[1] || (s[1] = (c) => ue(r)(e.predicate))
        }, null, 8, ["predicate", "is-in-add-compound-mode"]))
      ])
    ]));
  }
}, Rc = { class: "ui-predicate__row ui-predicate__row--comparison" }, qc = { class: "ui-predicate__col" }, wc = { class: "ui-predicate__col" }, jc = { class: "ui-predicate__col" }, Ec = { class: "ui-predicate__col" }, ts = {
  __name: "ui-predicate-comparison",
  props: {
    predicate: {
      type: Object,
      required: !0
    },
    columns: {
      type: Object,
      required: !0
    }
  },
  setup(e) {
    const t = e;
    ie("add");
    const r = ie("setPredicateTarget_id"), n = ie("setPredicateOperator_id"), i = ie("UITypes"), o = ie("getUIComponent"), a = (s) => {
      r(t.predicate, s);
    }, u = (s) => {
      n(t.predicate, s);
    };
    return (s, c) => {
      const p = be("ui-predicate-comparison-argument"), f = be("ui-predicate-options");
      return W(), Q("div", Rc, [
        fe("div", qc, [
          (W(), ge(Te(ue(o)(ue(i).TARGETS)), {
            class: "ui-predicate__targets",
            columns: e.columns,
            predicate: e.predicate,
            onChange: c[0] || (c[0] = (d) => a(d))
          }, null, 40, ["columns", "predicate"]))
        ]),
        fe("div", wc, [
          (W(), ge(Te(ue(o)(ue(i).OPERATORS)), {
            class: "ui-predicate__operators",
            columns: e.columns,
            predicate: e.predicate,
            onChange: c[1] || (c[1] = (d) => u(d))
          }, null, 40, ["columns", "predicate"]))
        ]),
        fe("div", jc, [
          ai(p, {
            class: "ui-predicate__arguments",
            predicate: e.predicate
          }, null, 8, ["predicate"])
        ]),
        fe("div", Ec, [
          ai(f, { predicate: e.predicate }, null, 8, ["predicate"])
        ])
      ]);
    };
  }
}, ns = {
  render() {
    return Bs(
      this.getArgumentTypeComponentById(
        this.predicate.operator.argumentType_id
      ),
      {
        value: this.predicate.argument,
        predicate: this.predicate,
        onChange: this._setArgumentValue
      }
    );
  },
  inject: ["getArgumentTypeComponentById", "setArgumentValue"],
  methods: {
    _setArgumentValue(e) {
      this.setArgumentValue(this.predicate, e);
    }
  },
  props: {
    predicate: {
      type: Object,
      required: !0
    }
  }
}, Ic = { class: "ui-predicate__row--compound" }, Cc = { class: "ui-predicate__row" }, Fc = { class: "ui-predicate__col" }, Bc = { class: "ui-predicate__col" }, is = {
  __name: "ui-predicate-compound",
  props: {
    predicate: {
      type: Object,
      required: !0
    },
    columns: {
      type: Object,
      required: !0
    }
  },
  setup(e) {
    const t = e, r = ie("setPredicateLogicalType_id"), n = ie("UITypes"), i = ie("getUIComponent"), o = (a) => r(t.predicate, a);
    return (a, u) => {
      const s = be("ui-predicate-options"), c = be("ui-predicate-compound", !0), p = be("ui-predicate-comparison");
      return W(), Q("div", Ic, [
        fe("div", Cc, [
          fe("div", Fc, [
            e.predicate.logic ? (W(), ge(Te(ue(i)(ue(n).LOGICAL_TYPES)), {
              key: 0,
              class: "ui-predicate__logic",
              predicate: e.predicate,
              columns: e.columns,
              onChange: u[0] || (u[0] = (f) => o(f))
            }, null, 40, ["predicate", "columns"])) : Ce("", !0)
          ]),
          fe("div", Bc, [
            ai(s, { predicate: e.predicate }, null, 8, ["predicate"])
          ])
        ]),
        (W(!0), Q(Se, null, Be(e.predicate.predicates, (f, d) => (W(), Q(Se, null, [
          f.$_type === "CompoundPredicate" ? (W(), ge(c, {
            predicate: f,
            key: d,
            columns: e.columns
          }, null, 8, ["predicate", "columns"])) : Ce("", !0),
          f.$_type === "ComparisonPredicate" ? (W(), ge(p, {
            predicate: f,
            key: d,
            columns: e.columns
          }, null, 8, ["predicate", "columns"])) : Ce("", !0)
        ], 64))), 256))
      ]);
    };
  }
};
var Hr, wo;
function pi() {
  if (wo) return Hr;
  wo = 1;
  function e(t, r) {
    return Object.prototype.hasOwnProperty.call(r, t);
  }
  return Hr = e, Hr;
}
var Kr, jo;
function os() {
  if (jo) return Kr;
  jo = 1;
  var e = /* @__PURE__ */ pi();
  function t(r) {
    if (r == null)
      throw new TypeError("Cannot convert undefined or null to object");
    for (var n = Object(r), i = 1, o = arguments.length; i < o; ) {
      var a = arguments[i];
      if (a != null)
        for (var u in a)
          e(u, a) && (n[u] = a[u]);
      i += 1;
    }
    return n;
  }
  return Kr = typeof Object.assign == "function" ? Object.assign : t, Kr;
}
var Xr, Eo;
function Ne() {
  if (Eo) return Xr;
  Eo = 1;
  function e(t) {
    return t != null && typeof t == "object" && t["@@functional/placeholder"] === !0;
  }
  return Xr = e, Xr;
}
var Yr, Io;
function de() {
  if (Io) return Yr;
  Io = 1;
  var e = /* @__PURE__ */ Ne();
  function t(r) {
    return function n(i) {
      return arguments.length === 0 || e(i) ? n : r.apply(this, arguments);
    };
  }
  return Yr = t, Yr;
}
var Zr, Co;
function li() {
  if (Co) return Zr;
  Co = 1;
  var e = /* @__PURE__ */ os(), t = /* @__PURE__ */ de(), r = /* @__PURE__ */ t(function(i) {
    return e.apply(null, [{}].concat(i));
  });
  return Zr = r, Zr;
}
var Qr, Fo;
function Mc() {
  if (Fo) return Qr;
  Fo = 1;
  const e = /* @__PURE__ */ li(), t = Qu();
  function r(n) {
    return {
      [n]: t(n)
    };
  }
  return Qr = e([
    /**
     * Error when a Predicate is created without
     * @typedef {Error} InvalidPredicateType
     * @memberof errors
     * @since 1.0.0
     */
    r("InvalidPredicateType"),
    /**
     * Error when a json data passed to PredicateCore Constructor is neither a serialized ComparisonPredicate or a CompoundPredicate
     * @typedef {Error} CompoundPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("UnknownJSONData"),
    /**
     * Error when someone tried to remove the last remaining predicate from a CompoundPredicate
     * @typedef {Error} CompoundPredicateMustHaveAtLeastOneSubPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("CompoundPredicateMustHaveAtLeastOneSubPredicate"),
    /**
     * Error when setData `data` parameter is called with something else than a  {@link dataclasses.CompoundPredicate}
     * @typedef {Error} RootPredicateMustBeACompoundPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("RootPredicateMustBeACompoundPredicate"),
    /**
     * Error when a function was requiring a {@link dataclasses.CompoundPredicate} as a parameter
     * @typedef {Error} RootPredicateMustBeACompoundPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("PredicateMustBeACompoundPredicate"),
    /**
     * Error when a function was requiring a {@link dataclasses.ComparisonPredicate} as a parameter
     * @typedef {Error} PredicateMustBeAComparisonPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("PredicateMustBeAComparisonPredicate"),
    /**
     * Error add is called with something else than "after" parameter
     * @typedef {Error} AddCurrentlyOnlySupportAfterInsertion
     * @memberof errors
     * @since 1.0.0
     */
    r("AddCurrentlyOnlySupportAfterInsertion"),
    /**
     * Thrown when a specified target refers to a undefined type.
     * It means the user has missed a type definition in `types`.
     * @typedef {Error} TargetMustReferToADefinedType
     * @memberof errors
     * @since 1.0.0
     */
    r("TargetMustReferToADefinedType"),
    /**
     * Thrown when a user asked for a logic change
     * but the logicalType_id was invalid because it referred to no existing targets
     * @typedef {Error} LogicalType_idMustReferToADefinedLogicalType
     * @memberof errors
     * @since 1.0.0
     */
    r("LogicalType_idMustReferToADefinedLogicalType"),
    /**
     * Thrown when a user asked for a target
     * but the target_id was invalid because it referred to no existing targets
     * @typedef {Error} Target_idMustReferToADefinedTarget
     * @memberof errors
     * @since 1.0.0
     */
    r("Target_idMustReferToADefinedTarget"),
    /**
     * Thrown when a user asked for a operator
     * but the operator_id was invalid because it referred to no existing operators
     * @typedef {Error} Operator_idMustReferToADefinedOperator
     * @memberof errors
     * @since 1.0.0
     */
    r("Operator_idMustReferToADefinedOperator"),
    /**
     * Thrown when a user asked for a operator change on a predicate
     * but the operator_id was invalid because it referred
     * to no existing operators for the currently selected predicate's target
     * @typedef {Error} Operator_idMustReferToADefinedOperator
     * @memberof errors
     * @since 1.0.0
     */
    r("Operator_idMustReferToADefinedOperator"),
    /**
     * Thrown when remove is called on root {@link dataclasses.CompoundPredicate}
     * @typedef {Error} ForbiddenCannotRemoveRootCompoundPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("ForbiddenCannotRemoveRootCompoundPredicate"),
    /**
     * Thrown when remove is called on root CompoundPredicate
     * @typedef {Error} ForbiddenCannotRemoveLastComparisonPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("ForbiddenCannotRemoveLastComparisonPredicate"),
    /**
     * Thrown when remove is called with an invalid type of predicate
     * @typedef {Error} CannotRemoveSomethingElseThanACompoundPredicateOrAComparisonPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("CannotRemoveSomethingElseThanACompoundPredicateOrAComparisonPredicate"),
    /**
     * Thrown when add is called with an invalid type of predicate
     * @typedef {Error} CannotAddSomethingElseThanACompoundPredicateOrAComparisonPredicate
     * @memberof errors
     * @since 1.0.0
     */
    r("CannotAddSomethingElseThanACompoundPredicateOrAComparisonPredicate"),
    /**
     * Thrown when the UI Framework adapter forgot to pass `getDefaultArgumentComponent` in the option object to UIPredicateCore constructor
     * @typedef {Error} UIFrameworkMustImplementgetDefaultArgumentComponent
     * @memberof errors
     * @since 1.0.0
     */
    r("UIFrameworkMustImplementgetDefaultArgumentComponent")
  ]), Qr;
}
var et, Bo;
function Lc() {
  return Bo || (Bo = 1, et = {
    TARGETS: "TARGETS",
    LOGICAL_TYPES: "LOGICAL_TYPES",
    OPERATORS: "OPERATORS",
    PREDICATE_ADD: "PREDICATE_ADD",
    PREDICATE_REMOVE: "PREDICATE_REMOVE",
    ARGUMENT_DEFAULT: "ARGUMENT_DEFAULT"
  }), et;
}
var rt, Mo;
function Dc() {
  return Mo || (Mo = 1, rt = {
    /**
     * @param {CompoundPredicate} root      root predicate
     * @param {Predicate} predicateToRemove predicate to remove
     * @return {boolean} true if the predicate to remove is the root predicate
     * @memberof rules
     */
    predicateToRemoveIsRootPredicate: (e, t) => e === t,
    /**
     * @param {CompoundPredicate} root      root predicate
     * @param {Function} CompoundPredicate CompoundPredicate constructor function
     * @param {Function} ComparisonPredicate ComparisonPredicate constructor function
     * @return {boolean} true if the predicate to remove is the last ComparisonPredicate
     * @memberof rules
     */
    predicateToRemoveIsTheLastComparisonPredicate: (e, t, r) => t.reduce(
      e,
      (i, o) => r.is(o) ? i + 1 : i,
      0
    ) === 1
  }), rt;
}
var tt, Lo;
function Nc() {
  return Lo || (Lo = 1, tt = ({ errors: e, rules: t }) => ({
    /**
     * [CompoundPredicateMustHaveAtLeastOneSubPredicate description]
     * @param {?Array<Predicate>} predicates list of predicates to add to a CompoundPredicate at creation time
     * @return {Promise<undefined, errors.CompoundPredicateMustHaveAtLeastOneSubPredicate>} resolve the promise if the invariant pass or yield a `CompoundPredicateMustHaveAtLeastOneSubPredicate` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    CompoundPredicateMustHaveAtLeastOneSubPredicate: (r) => !Array.isArray(r) || r.length === 0 ? Promise.reject(
      new e.CompoundPredicateMustHaveAtLeastOneSubPredicate()
    ) : Promise.resolve(),
    /**
     * @param {String} type Predicate type
     * @param {Object} acceptedTypes list of accepted types
     * @return {Promise<undefined, errors.InvalidPredicateType>} resolve the promise if the invariant pass or yield a `InvalidPredicateType` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    PredicateTypeMustBeValid: (r, n) => Object.keys(n).includes(r) ? Promise.resolve() : Promise.reject(new e.InvalidPredicateType()),
    /**
     * @param {dataclasses.CompoundPredicate} root root
     * @param {dataclasses.CompoundPredicate} CompoundPredicate CompoundPredicate
     * @return {Promise<dataclasses.CompoundPredicate, errors.RootPredicateMustBeACompoundPredicate>} resolve the promise if the invariant pass or yield a `RootPredicateMustBeACompoundPredicate` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    RootPredicateMustBeACompoundPredicate: (r, n) => n.is(r) ? Promise.resolve(r) : Promise.reject(new e.RootPredicateMustBeACompoundPredicate()),
    /**
     * @param {dataclasses.Predicate} predicate predicate
     * @param {dataclasses.ComparisonPredicate} ComparisonPredicate ComparisonPredicate constructor
     * @return {Promise<undefined, errors.PredicateMustBeAComparisonPredicate>} resolve the promise if the invariant pass or yield a `PredicateMustBeAComparisonPredicate` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    PredicateMustBeAComparisonPredicate: (r, n) => n.is(r) ? Promise.resolve() : Promise.reject(new e.PredicateMustBeAComparisonPredicate()),
    /**
     * @param {dataclasses.Predicate} predicate predicate
     * @param {dataclasses.CompoundPredicate} CompoundPredicate CompoundPredicate constructor
     * @return {Promise<undefined, errors.PredicateMustBeACompoundPredicate>} resolve the promise if the invariant pass or yield a `PredicateMustBeACompoundPredicate` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    PredicateMustBeACompoundPredicate: (r, n) => n.is(r) ? Promise.resolve() : Promise.reject(new e.PredicateMustBeACompoundPredicate()),
    /**
     * @param {string} how how
     * @return {Promise<undefined, errors.AddCurrentlyOnlySupportAfterInsertion>} resolve the promise if the invariant pass or yield a `AddOnlySupportsAfter` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    AddOnlySupportsAfter: (r) => r !== "after" ? Promise.reject(new e.AddCurrentlyOnlySupportAfterInsertion()) : Promise.resolve(),
    /**
     * @param {option<dataclasses.Type>} type type
     * @param {dataclasses.Target} target target
     * @return {Promise<dataclasses.Type, errors.TargetMustReferToADefinedType>} resolve the promise if the invariant pass or yield a `TargetMustReferToADefinedType` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    TargetMustReferToADefinedType: (r, n) => r.isNone() ? Promise.reject(
      new e.TargetMustReferToADefinedType(
        `target ${JSON.stringify(
          n.target_id
        )} does not refer to a defined type, target.type_id=${JSON.stringify(
          n.type_id
        )}`
      )
    ) : Promise.resolve(r.value()),
    /**
     * @param {Option<dataclasses.LogicalType>} logicalType logicalType
     * @return {Promise<dataclasses.LogicalType, errors.LogicalType_idMustReferToADefinedLogicalType>} resolve the promise if the invariant pass or yield a `LogicalType_idMustReferToADefinedLogicalType` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    LogicalType_idMustReferToADefinedLogicalType: (r) => r ? Promise.resolve(r) : Promise.reject(
      new e.LogicalType_idMustReferToADefinedLogicalType()
    ),
    /**
     * @param {Option<dataclasses.Target>} target target
     * @return {Promise<dataclasses.Target, errors.Target_idMustReferToADefinedTarget>} resolve the promise if the invariant pass or yield a `Target_idMustReferToADefinedTarget` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    Target_idMustReferToADefinedTarget: (r) => r ? Promise.resolve(r) : Promise.reject(new e.Target_idMustReferToADefinedTarget()),
    /**
     * @param {Option<dataclasses.Operator>} operator operator
     * @return {Promise<dataclasses.Operator, errors.Operator_idMustReferToADefinedOperator>} resolve the promise if the invariant pass or yield a `Operator_idMustReferToADefinedOperator` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    Operator_idMustReferToADefinedOperator: (r) => r ? Promise.resolve(r) : Promise.reject(new e.Operator_idMustReferToADefinedOperator()),
    /**
     * @param {dataclasses.CompoundPredicate} root root
     * @param {dataclasses.Predicate} predicateToRemove predicateToRemove
     * @return {Promise<predicateToRemove, errors.ForbiddenCannotRemoveRootCompoundPredicate>} resolve the promise if the invariant pass or yield a `ForbiddenCannotRemoveRootCompoundPredicate` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    RemovePredicateMustDifferFromRootPredicate: (r, n) => t.predicateToRemoveIsRootPredicate(r, n) ? Promise.reject(new e.ForbiddenCannotRemoveRootCompoundPredicate()) : Promise.resolve(n),
    /**
     * @param {dataclasses.CompoundPredicate} root root
     * @param {dataclasses.Predicate} predicateToRemove predicateToRemove
     * @param {dataclasses.CompoundPredicate} CompoundPredicate CompoundPredicate
     * @param {dataclasses.ComparisonPredicate} ComparisonPredicate ComparisonPredicate
     * @return {Promise<undefined, errors.ForbiddenCannotRemoveLastComparisonPredicate>} resolve the promise if the invariant pass or yield a `RootPredicateMustBeACompoundPredicate` error otherwise
     * @memberof invariants
     * @since 1.0.0
     */
    RemovePredicateCannotBeTheLastComparisonPredicate: (r, n, i, o) => o.is(n) && t.predicateToRemoveIsTheLastComparisonPredicate(
      r,
      i,
      o
    ) ? Promise.reject(
      new e.ForbiddenCannotRemoveLastComparisonPredicate()
    ) : Promise.resolve()
  })), tt;
}
var nt, Do;
function oe() {
  if (Do) return nt;
  Do = 1;
  var e = /* @__PURE__ */ de(), t = /* @__PURE__ */ Ne();
  function r(n) {
    return function i(o, a) {
      switch (arguments.length) {
        case 0:
          return i;
        case 1:
          return t(o) ? i : e(function(u) {
            return n(o, u);
          });
        default:
          return t(o) && t(a) ? i : t(o) ? e(function(u) {
            return n(u, a);
          }) : t(a) ? e(function(u) {
            return n(o, u);
          }) : n(o, a);
      }
    };
  }
  return nt = r, nt;
}
var it, No;
function di() {
  if (No) return it;
  No = 1;
  var e = /* @__PURE__ */ os(), t = /* @__PURE__ */ oe(), r = /* @__PURE__ */ t(function(i, o) {
    return e({}, i, o);
  });
  return it = r, it;
}
var ot, $o;
function $c() {
  if ($o) return ot;
  $o = 1;
  var e = /* @__PURE__ */ de(), t = `	
\v\f\r   ᠎             　\u2028\u2029\uFEFF`, r = "​", n = typeof String.prototype.trim == "function", i = /* @__PURE__ */ e(!n || /* @__PURE__ */ t.trim() || !/* @__PURE__ */ r.trim() ? function(a) {
    var u = new RegExp("^[" + t + "][" + t + "]*"), s = new RegExp("[" + t + "][" + t + "]*$");
    return a.replace(u, "").replace(s, "");
  } : function(a) {
    return a.trim();
  });
  return ot = i, ot;
}
var at, Uo;
function as() {
  return Uo || (Uo = 1, at = function(t) {
    return {
      $_type: t
    };
  }), at;
}
var ut, ko;
function us() {
  if (ko) return ut;
  ko = 1;
  const e = /* @__PURE__ */ li(), t = /* @__PURE__ */ $c(), r = as();
  function n(f) {
    const { target_id: d, label: P, type_id: _ } = p(
      f,
      "target_id,label,type_id"
    );
    return e([r("Target"), { target_id: d, label: P, type_id: _ }, f]);
  }
  n.toJSON = function(f) {
    return {
      target_id: f.target_id
    };
  };
  function i(f) {
    const { operator_id: d, label: P, argumentType_id: _ } = p(
      f,
      "operator_id,label,argumentType_id"
    );
    return e([
      r("Operator"),
      {
        operator_id: d,
        argumentType_id: _,
        label: P
      },
      f
    ]);
  }
  i.toJSON = function(f) {
    return {
      operator_id: f.operator_id
    };
  };
  function o(f) {
    const { type_id: d, operator_ids: P } = p(f, "type_id,operator_ids");
    return e([
      r("Type"),
      {
        type_id: d,
        operator_ids: P
      },
      f
    ]);
  }
  function a(f) {
    const { logicalType_id: d, label: P } = p(
      f,
      "logicalType_id,label"
    );
    return e([
      r("LogicalType"),
      {
        logicalType_id: d,
        label: P
      },
      f
    ]);
  }
  a.toJSON = function(f) {
    return {
      logicalType_id: f.logicalType_id
    };
  };
  function u(f) {
    const { argumentType_id: d, component: P } = p(
      f,
      "argumentType_id,component"
    );
    return e([
      r("ArgumentType"),
      {
        argumentType_id: d,
        component: P
      },
      f
    ]);
  }
  const s = Object.prototype.toString;
  function c(f) {
    return s.call(f) === "[object Object]";
  }
  function p(f, d) {
    if (!c(f))
      throw new Error(`Object is required, got: ${JSON.stringify(f)}.`);
    const P = d.split(",").map(t);
    let _;
    for (; _ = P.pop(); )
      if (!f.hasOwnProperty(_))
        throw new Error(
          `Object ${JSON.stringify(f)} MUST have a '${_}' property.`
        );
    return f;
  }
  return ut = {
    Type: o,
    Target: n,
    Operator: i,
    LogicalType: a,
    ArgumentType: u,
    _requireProps: p
  }, ut;
}
var st, xo;
function Uc() {
  if (xo) return st;
  xo = 1;
  const e = /* @__PURE__ */ di(), t = /* @__PURE__ */ li(), r = as();
  return st = ({ invariants: n, errors: i }) => {
    const { Target: o, Operator: a, LogicalType: u } = us();
    function s(f) {
      return n.PredicateTypeMustBeValid(f.$name, s.Types).then(
        () => e(r(f.$name), {
          /**
           * $canBeRemoved specify if the predicate can be removed or not from the Predicates tree
           * @type {Boolean}
           *  @memberof Predicate
           */
          $canBeRemoved: !0
        })
      );
    }
    s.toJSON = function(f) {
      return c.is(f) ? c.toJSON(f) : p.toJSON(f);
    }, s.fromJSON = function(f, d) {
      return c.isFromJSON(f) ? c.fromJSON(f, d) : p.isFromJSON(f) ? p.fromJSON(f, d) : Promise.reject(new i.UnknownJSONData());
    }, s.Types = {
      ComparisonPredicate: "ComparisonPredicate",
      CompoundPredicate: "CompoundPredicate"
    };
    function c(f, d, P = null) {
      return s(c).then(
        (_) => e(_, {
          target: f,
          operator: d,
          argument: P
        })
      );
    }
    c.$name = s.Types.ComparisonPredicate, c.toJSON = function(f) {
      return t([
        o.toJSON(f.target),
        a.toJSON(f.operator),
        {
          argument: f.argument
        }
      ]);
    }, c.fromJSON = function(f, d) {
      return Promise.all([
        d.getTargetById(f.target_id),
        d.getOperatorById(f.operator_id)
      ]).then(
        ([P, _]) => c(P, _, f.argument)
      );
    }, c.is = (f) => f && f.$_type === s.Types.ComparisonPredicate, c.isFromJSON = (f) => f && f.target_id;
    function p(f, d) {
      return n.CompoundPredicateMustHaveAtLeastOneSubPredicate(
        d,
        p
      ).then(() => s(p)).then(
        (P) => e(P, {
          logic: f,
          predicates: d
        })
      );
    }
    return p.$name = s.Types.CompoundPredicate, p.toJSON = function(f) {
      return t([
        u.toJSON(f.logic),
        { predicates: f.predicates.map(s.toJSON) }
      ]);
    }, p.fromJSON = function(f, d) {
      return n.CompoundPredicateMustHaveAtLeastOneSubPredicate(
        f.predicates,
        p
      ).then(() => d.getLogicalTypeById(f.logicalType_id)).then(
        (P) => Promise.all(
          f.predicates.map(
            (_) => s.fromJSON(_, d)
          )
        ).then((_) => p(P, _))
      );
    }, p.reduce = function(f, d, P, _ = []) {
      const w = d(P, f, _);
      return f.predicates.reduce((O, j, I) => {
        const E = _.concat([f, [j, I]]);
        return p.is(j) ? p.reduce(j, d, O, E) : d(O, j, E);
      }, w);
    }, p.forEach = (f, d) => {
      p.reduce(
        f,
        (P, _) => {
          d(_);
        },
        null
      );
    }, p.is = (f) => f && f.$_type === s.Types.CompoundPredicate, p.isFromJSON = (f) => f && f.logicalType_id, {
      Predicate: s,
      ComparisonPredicate: c,
      CompoundPredicate: p
    };
  }, st;
}
var ct, Go;
function kc() {
  if (Go) return ct;
  Go = 1;
  const e = /* @__PURE__ */ di();
  return ct = (t) => e(us(), Uc()(t)), ct;
}
var ft, Vo;
function yi() {
  return Vo || (Vo = 1, ft = Array.isArray || function(t) {
    return t != null && t.length >= 0 && Object.prototype.toString.call(t) === "[object Array]";
  }), ft;
}
var pt, Jo;
function xc() {
  if (Jo) return pt;
  Jo = 1;
  function e(t) {
    return t != null && typeof t["@@transducer/step"] == "function";
  }
  return pt = e, pt;
}
var lt, Wo;
function $e() {
  if (Wo) return lt;
  Wo = 1;
  var e = /* @__PURE__ */ yi(), t = /* @__PURE__ */ xc();
  function r(n, i, o) {
    return function() {
      if (arguments.length === 0)
        return o();
      var a = Array.prototype.slice.call(arguments, 0), u = a.pop();
      if (!e(u)) {
        for (var s = 0; s < n.length; ) {
          if (typeof u[n[s]] == "function")
            return u[n[s]].apply(u, a);
          s += 1;
        }
        if (t(u)) {
          var c = i.apply(null, a);
          return c(u);
        }
      }
      return o.apply(this, arguments);
    };
  }
  return lt = r, lt;
}
var dt, zo;
function Gc() {
  if (zo) return dt;
  zo = 1;
  function e(t) {
    return t && t["@@transducer/reduced"] ? t : {
      "@@transducer/value": t,
      "@@transducer/reduced": !0
    };
  }
  return dt = e, dt;
}
var yt, Ho;
function Ue() {
  return Ho || (Ho = 1, yt = {
    init: function() {
      return this.xf["@@transducer/init"]();
    },
    result: function(e) {
      return this.xf["@@transducer/result"](e);
    }
  }), yt;
}
var vt, Ko;
function Vc() {
  if (Ko) return vt;
  Ko = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ Gc(), r = /* @__PURE__ */ Ue(), n = /* @__PURE__ */ function() {
    function o(a, u) {
      this.xf = u, this.f = a, this.found = !1;
    }
    return o.prototype["@@transducer/init"] = r.init, o.prototype["@@transducer/result"] = function(a) {
      return this.found || (a = this.xf["@@transducer/step"](a, void 0)), this.xf["@@transducer/result"](a);
    }, o.prototype["@@transducer/step"] = function(a, u) {
      return this.f(u) && (this.found = !0, a = t(this.xf["@@transducer/step"](a, u))), a;
    }, o;
  }(), i = /* @__PURE__ */ e(function(a, u) {
    return new n(a, u);
  });
  return vt = i, vt;
}
var gt, Xo;
function Jc() {
  if (Xo) return gt;
  Xo = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ $e(), r = /* @__PURE__ */ Vc(), n = /* @__PURE__ */ e(/* @__PURE__ */ t(["find"], r, function(o, a) {
    for (var u = 0, s = a.length; u < s; ) {
      if (o(a[u]))
        return a[u];
      u += 1;
    }
  }));
  return gt = n, gt;
}
var ht, Yo;
function ke() {
  if (Yo) return ht;
  Yo = 1;
  function e(t, r) {
    switch (t) {
      case 0:
        return function() {
          return r.apply(this, arguments);
        };
      case 1:
        return function(n) {
          return r.apply(this, arguments);
        };
      case 2:
        return function(n, i) {
          return r.apply(this, arguments);
        };
      case 3:
        return function(n, i, o) {
          return r.apply(this, arguments);
        };
      case 4:
        return function(n, i, o, a) {
          return r.apply(this, arguments);
        };
      case 5:
        return function(n, i, o, a, u) {
          return r.apply(this, arguments);
        };
      case 6:
        return function(n, i, o, a, u, s) {
          return r.apply(this, arguments);
        };
      case 7:
        return function(n, i, o, a, u, s, c) {
          return r.apply(this, arguments);
        };
      case 8:
        return function(n, i, o, a, u, s, c, p) {
          return r.apply(this, arguments);
        };
      case 9:
        return function(n, i, o, a, u, s, c, p, f) {
          return r.apply(this, arguments);
        };
      case 10:
        return function(n, i, o, a, u, s, c, p, f, d) {
          return r.apply(this, arguments);
        };
      default:
        throw new Error("First argument to _arity must be a non-negative integer no greater than ten");
    }
  }
  return ht = e, ht;
}
var mt, Zo;
function Wc() {
  if (Zo) return mt;
  Zo = 1;
  var e = /* @__PURE__ */ ke(), t = /* @__PURE__ */ Ne();
  function r(n, i, o) {
    return function() {
      for (var a = [], u = 0, s = n, c = 0; c < i.length || u < arguments.length; ) {
        var p;
        c < i.length && (!t(i[c]) || u >= arguments.length) ? p = i[c] : (p = arguments[u], u += 1), a[c] = p, t(p) || (s -= 1), c += 1;
      }
      return s <= 0 ? o.apply(this, a) : e(s, r(n, a, o));
    };
  }
  return mt = r, mt;
}
var _t, Qo;
function ss() {
  if (Qo) return _t;
  Qo = 1;
  var e = /* @__PURE__ */ ke(), t = /* @__PURE__ */ de(), r = /* @__PURE__ */ oe(), n = /* @__PURE__ */ Wc(), i = /* @__PURE__ */ r(function(a, u) {
    return a === 1 ? t(u) : e(a, n(a, [], u));
  });
  return _t = i, _t;
}
var bt, ea;
function zc() {
  if (ea) return bt;
  ea = 1;
  var e = /* @__PURE__ */ de(), t = /* @__PURE__ */ ss(), r = /* @__PURE__ */ e(function(i) {
    return t(i.length, i);
  });
  return bt = r, bt;
}
var At, ra;
function Hc() {
  if (ra) return At;
  ra = 1;
  function e(t, r) {
    return function() {
      return r.call(this, t.apply(this, arguments));
    };
  }
  return At = e, At;
}
var Pt, ta;
function vi() {
  if (ta) return Pt;
  ta = 1;
  var e = /* @__PURE__ */ de(), t = /* @__PURE__ */ oe(), r = /* @__PURE__ */ Ne();
  function n(i) {
    return function o(a, u, s) {
      switch (arguments.length) {
        case 0:
          return o;
        case 1:
          return r(a) ? o : t(function(c, p) {
            return i(a, c, p);
          });
        case 2:
          return r(a) && r(u) ? o : r(a) ? t(function(c, p) {
            return i(c, u, p);
          }) : r(u) ? t(function(c, p) {
            return i(a, c, p);
          }) : e(function(c) {
            return i(a, u, c);
          });
        default:
          return r(a) && r(u) && r(s) ? o : r(a) && r(u) ? t(function(c, p) {
            return i(c, p, s);
          }) : r(a) && r(s) ? t(function(c, p) {
            return i(c, u, p);
          }) : r(u) && r(s) ? t(function(c, p) {
            return i(a, c, p);
          }) : r(a) ? e(function(c) {
            return i(c, u, s);
          }) : r(u) ? e(function(c) {
            return i(a, c, s);
          }) : r(s) ? e(function(c) {
            return i(a, u, c);
          }) : i(a, u, s);
      }
    };
  }
  return Pt = n, Pt;
}
var Ot, na;
function Kc() {
  if (na) return Ot;
  na = 1;
  function e(t) {
    return Object.prototype.toString.call(t) === "[object String]";
  }
  return Ot = e, Ot;
}
var Tt, ia;
function Xc() {
  if (ia) return Tt;
  ia = 1;
  var e = /* @__PURE__ */ de(), t = /* @__PURE__ */ yi(), r = /* @__PURE__ */ Kc(), n = /* @__PURE__ */ e(function(o) {
    return t(o) ? !0 : !o || typeof o != "object" || r(o) ? !1 : o.nodeType === 1 ? !!o.length : o.length === 0 ? !0 : o.length > 0 ? o.hasOwnProperty(0) && o.hasOwnProperty(o.length - 1) : !1;
  });
  return Tt = n, Tt;
}
var St, oa;
function Yc() {
  if (oa) return St;
  oa = 1;
  var e = /* @__PURE__ */ function() {
    function r(n) {
      this.f = n;
    }
    return r.prototype["@@transducer/init"] = function() {
      throw new Error("init not implemented on XWrap");
    }, r.prototype["@@transducer/result"] = function(n) {
      return n;
    }, r.prototype["@@transducer/step"] = function(n, i) {
      return this.f(n, i);
    }, r;
  }();
  function t(r) {
    return new e(r);
  }
  return St = t, St;
}
var Rt, aa;
function Zc() {
  if (aa) return Rt;
  aa = 1;
  var e = /* @__PURE__ */ ke(), t = /* @__PURE__ */ oe(), r = /* @__PURE__ */ t(function(i, o) {
    return e(i.length, function() {
      return i.apply(o, arguments);
    });
  });
  return Rt = r, Rt;
}
var qt, ua;
function gi() {
  if (ua) return qt;
  ua = 1;
  var e = /* @__PURE__ */ Xc(), t = /* @__PURE__ */ Yc(), r = /* @__PURE__ */ Zc();
  function n(s, c, p) {
    for (var f = 0, d = p.length; f < d; ) {
      if (c = s["@@transducer/step"](c, p[f]), c && c["@@transducer/reduced"]) {
        c = c["@@transducer/value"];
        break;
      }
      f += 1;
    }
    return s["@@transducer/result"](c);
  }
  function i(s, c, p) {
    for (var f = p.next(); !f.done; ) {
      if (c = s["@@transducer/step"](c, f.value), c && c["@@transducer/reduced"]) {
        c = c["@@transducer/value"];
        break;
      }
      f = p.next();
    }
    return s["@@transducer/result"](c);
  }
  function o(s, c, p, f) {
    return s["@@transducer/result"](p[f](r(s["@@transducer/step"], s), c));
  }
  var a = typeof Symbol < "u" ? Symbol.iterator : "@@iterator";
  function u(s, c, p) {
    if (typeof s == "function" && (s = t(s)), e(p))
      return n(s, c, p);
    if (typeof p["fantasy-land/reduce"] == "function")
      return o(s, c, p, "fantasy-land/reduce");
    if (p[a] != null)
      return i(s, c, p[a]());
    if (typeof p.next == "function")
      return i(s, c, p);
    if (typeof p.reduce == "function")
      return o(s, c, p, "reduce");
    throw new TypeError("reduce: list must be array or iterable");
  }
  return qt = u, qt;
}
var wt, sa;
function Qc() {
  if (sa) return wt;
  sa = 1;
  var e = /* @__PURE__ */ vi(), t = /* @__PURE__ */ gi(), r = /* @__PURE__ */ e(t);
  return wt = r, wt;
}
var jt, ca;
function cs() {
  if (ca) return jt;
  ca = 1;
  var e = /* @__PURE__ */ yi();
  function t(r, n) {
    return function() {
      var i = arguments.length;
      if (i === 0)
        return n();
      var o = arguments[i - 1];
      return e(o) || typeof o[r] != "function" ? n.apply(this, arguments) : o[r].apply(o, Array.prototype.slice.call(arguments, 0, i - 1));
    };
  }
  return jt = t, jt;
}
var Et, fa;
function fs() {
  if (fa) return Et;
  fa = 1;
  var e = /* @__PURE__ */ cs(), t = /* @__PURE__ */ vi(), r = /* @__PURE__ */ t(/* @__PURE__ */ e("slice", function(i, o, a) {
    return Array.prototype.slice.call(a, i, o);
  }));
  return Et = r, Et;
}
var It, pa;
function ef() {
  if (pa) return It;
  pa = 1;
  var e = /* @__PURE__ */ cs(), t = /* @__PURE__ */ de(), r = /* @__PURE__ */ fs(), n = /* @__PURE__ */ t(/* @__PURE__ */ e("tail", /* @__PURE__ */ r(1, 1 / 0)));
  return It = n, It;
}
var Ct, la;
function rf() {
  if (la) return Ct;
  la = 1;
  var e = /* @__PURE__ */ ke(), t = /* @__PURE__ */ Hc(), r = /* @__PURE__ */ Qc(), n = /* @__PURE__ */ ef();
  function i() {
    if (arguments.length === 0)
      throw new Error("pipe requires at least one argument");
    return e(arguments[0].length, r(t, arguments[0], n(arguments)));
  }
  return Ct = i, Ct;
}
var Ft, da;
function tf() {
  if (da) return Ft;
  da = 1;
  function e(t, r) {
    for (var n = 0, i = r.length, o = []; n < i; )
      t(r[n]) && (o[o.length] = r[n]), n += 1;
    return o;
  }
  return Ft = e, Ft;
}
var Bt, ya;
function nf() {
  if (ya) return Bt;
  ya = 1;
  function e(t) {
    return Object.prototype.toString.call(t) === "[object Object]";
  }
  return Bt = e, Bt;
}
var Mt, va;
function of() {
  if (va) return Mt;
  va = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ Ue(), r = /* @__PURE__ */ function() {
    function i(o, a) {
      this.xf = a, this.f = o;
    }
    return i.prototype["@@transducer/init"] = t.init, i.prototype["@@transducer/result"] = t.result, i.prototype["@@transducer/step"] = function(o, a) {
      return this.f(a) ? this.xf["@@transducer/step"](o, a) : o;
    }, i;
  }(), n = /* @__PURE__ */ e(function(o, a) {
    return new r(o, a);
  });
  return Mt = n, Mt;
}
var Lt, ga;
function af() {
  if (ga) return Lt;
  ga = 1;
  var e = /* @__PURE__ */ pi(), t = Object.prototype.toString, r = /* @__PURE__ */ function() {
    return t.call(arguments) === "[object Arguments]" ? function(i) {
      return t.call(i) === "[object Arguments]";
    } : function(i) {
      return e("callee", i);
    };
  }();
  return Lt = r, Lt;
}
var Dt, ha;
function ps() {
  if (ha) return Dt;
  ha = 1;
  var e = /* @__PURE__ */ de(), t = /* @__PURE__ */ pi(), r = /* @__PURE__ */ af(), n = !/* @__PURE__ */ { toString: null }.propertyIsEnumerable("toString"), i = ["constructor", "valueOf", "isPrototypeOf", "toString", "propertyIsEnumerable", "hasOwnProperty", "toLocaleString"], o = /* @__PURE__ */ function() {
    return arguments.propertyIsEnumerable("length");
  }(), a = function(c, p) {
    for (var f = 0; f < c.length; ) {
      if (c[f] === p)
        return !0;
      f += 1;
    }
    return !1;
  }, u = /* @__PURE__ */ e(typeof Object.keys == "function" && !o ? function(c) {
    return Object(c) !== c ? [] : Object.keys(c);
  } : function(c) {
    if (Object(c) !== c)
      return [];
    var p, f, d = [], P = o && r(c);
    for (p in c)
      t(p, c) && (!P || p !== "length") && (d[d.length] = p);
    if (n)
      for (f = i.length - 1; f >= 0; )
        p = i[f], t(p, c) && !a(d, p) && (d[d.length] = p), f -= 1;
    return d;
  });
  return Dt = u, Dt;
}
var Nt, ma;
function uf() {
  if (ma) return Nt;
  ma = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ $e(), r = /* @__PURE__ */ tf(), n = /* @__PURE__ */ nf(), i = /* @__PURE__ */ gi(), o = /* @__PURE__ */ of(), a = /* @__PURE__ */ ps(), u = /* @__PURE__ */ e(/* @__PURE__ */ t(["filter"], o, function(s, c) {
    return n(c) ? i(function(p, f) {
      return s(c[f]) && (p[f] = c[f]), p;
    }, {}, a(c)) : (
      // else
      r(s, c)
    );
  }));
  return Nt = u, Nt;
}
var $t, _a;
function sf() {
  if (_a) return $t;
  _a = 1;
  function e(t, r) {
    for (var n = 0, i = r.length, o = Array(i); n < i; )
      o[n] = t(r[n]), n += 1;
    return o;
  }
  return $t = e, $t;
}
var Ut, ba;
function cf() {
  if (ba) return Ut;
  ba = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ Ue(), r = /* @__PURE__ */ function() {
    function i(o, a) {
      this.xf = a, this.f = o;
    }
    return i.prototype["@@transducer/init"] = t.init, i.prototype["@@transducer/result"] = t.result, i.prototype["@@transducer/step"] = function(o, a) {
      return this.xf["@@transducer/step"](o, this.f(a));
    }, i;
  }(), n = /* @__PURE__ */ e(function(o, a) {
    return new r(o, a);
  });
  return Ut = n, Ut;
}
var kt, Aa;
function ff() {
  if (Aa) return kt;
  Aa = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ $e(), r = /* @__PURE__ */ sf(), n = /* @__PURE__ */ gi(), i = /* @__PURE__ */ cf(), o = /* @__PURE__ */ ss(), a = /* @__PURE__ */ ps(), u = /* @__PURE__ */ e(/* @__PURE__ */ t(["fantasy-land/map", "map"], i, function(c, p) {
    switch (Object.prototype.toString.call(p)) {
      case "[object Function]":
        return o(p.length, function() {
          return c.call(this, p.apply(this, arguments));
        });
      case "[object Object]":
        return n(function(f, d) {
          return f[d] = c(p[d]), f;
        }, {}, a(p));
      default:
        return r(c, p);
    }
  }));
  return kt = u, kt;
}
var xt, Pa;
function pf() {
  if (Pa) return xt;
  Pa = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ Ue(), r = /* @__PURE__ */ function() {
    function i(o, a) {
      this.xf = a, this.n = o;
    }
    return i.prototype["@@transducer/init"] = t.init, i.prototype["@@transducer/result"] = t.result, i.prototype["@@transducer/step"] = function(o, a) {
      return this.n > 0 ? (this.n -= 1, o) : this.xf["@@transducer/step"](o, a);
    }, i;
  }(), n = /* @__PURE__ */ e(function(o, a) {
    return new r(o, a);
  });
  return xt = n, xt;
}
var Gt, Oa;
function lf() {
  if (Oa) return Gt;
  Oa = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ $e(), r = /* @__PURE__ */ pf(), n = /* @__PURE__ */ fs(), i = /* @__PURE__ */ e(/* @__PURE__ */ t(["drop"], r, function(a, u) {
    return n(Math.max(0, a), 1 / 0, u);
  }));
  return Gt = i, Gt;
}
var Vt, Ta;
function df() {
  if (Ta) return Vt;
  Ta = 1;
  var e = /* @__PURE__ */ oe(), t = /* @__PURE__ */ lf(), r = /* @__PURE__ */ e(function(i, o) {
    return t(i >= 0 ? o.length - i : 0, o);
  });
  return Vt = r, Vt;
}
var Jt, Sa;
function yf() {
  if (Sa) return Jt;
  Sa = 1;
  var e = /* @__PURE__ */ vi(), t = /* @__PURE__ */ e(function(n, i, o) {
    n = n < o.length && n >= 0 ? n : o.length;
    var a = Array.prototype.slice.call(o, 0);
    return a.splice(n, 0, i), a;
  });
  return Jt = t, Jt;
}
var Wt = {}, Ra;
function vf() {
  return Ra || (Ra = 1, function(e) {
    e.none = /* @__PURE__ */ Object.create({
      value: function() {
        throw new Error("Called value on none");
      },
      isNone: function() {
        return !0;
      },
      isSome: function() {
        return !1;
      },
      map: function() {
        return e.none;
      },
      flatMap: function() {
        return e.none;
      },
      filter: function() {
        return e.none;
      },
      toArray: function() {
        return [];
      },
      orElse: t,
      valueOrElse: t
    });
    function t(n) {
      return typeof n == "function" ? n() : n;
    }
    e.some = function(n) {
      return new r(n);
    };
    var r = function(n) {
      this._value = n;
    };
    r.prototype.value = function() {
      return this._value;
    }, r.prototype.isNone = function() {
      return !1;
    }, r.prototype.isSome = function() {
      return !0;
    }, r.prototype.map = function(n) {
      return new r(n(this._value));
    }, r.prototype.flatMap = function(n) {
      return n(this._value);
    }, r.prototype.filter = function(n) {
      return n(this._value) ? this : e.none;
    }, r.prototype.toArray = function() {
      return [this._value];
    }, r.prototype.orElse = function(n) {
      return this;
    }, r.prototype.valueOrElse = function(n) {
      return this._value;
    }, e.isOption = function(n) {
      return n === e.none || n instanceof r;
    }, e.fromNullable = function(n) {
      return n == null ? e.none : new r(n);
    };
  }(Wt)), Wt;
}
var Ie = { exports: {} }, qa;
function gf() {
  if (qa) return Ie.exports;
  qa = 1;
  var e = typeof Reflect == "object" ? Reflect : null, t = e && typeof e.apply == "function" ? e.apply : function(h, b, A) {
    return Function.prototype.apply.call(h, b, A);
  }, r;
  e && typeof e.ownKeys == "function" ? r = e.ownKeys : Object.getOwnPropertySymbols ? r = function(h) {
    return Object.getOwnPropertyNames(h).concat(Object.getOwnPropertySymbols(h));
  } : r = function(h) {
    return Object.getOwnPropertyNames(h);
  };
  function n(g) {
    console && console.warn && console.warn(g);
  }
  var i = Number.isNaN || function(h) {
    return h !== h;
  };
  function o() {
    o.init.call(this);
  }
  Ie.exports = o, Ie.exports.once = j, o.EventEmitter = o, o.prototype._events = void 0, o.prototype._eventsCount = 0, o.prototype._maxListeners = void 0;
  var a = 10;
  function u(g) {
    if (typeof g != "function")
      throw new TypeError('The "listener" argument must be of type Function. Received type ' + typeof g);
  }
  Object.defineProperty(o, "defaultMaxListeners", {
    enumerable: !0,
    get: function() {
      return a;
    },
    set: function(g) {
      if (typeof g != "number" || g < 0 || i(g))
        throw new RangeError('The value of "defaultMaxListeners" is out of range. It must be a non-negative number. Received ' + g + ".");
      a = g;
    }
  }), o.init = function() {
    (this._events === void 0 || this._events === Object.getPrototypeOf(this)._events) && (this._events = /* @__PURE__ */ Object.create(null), this._eventsCount = 0), this._maxListeners = this._maxListeners || void 0;
  }, o.prototype.setMaxListeners = function(h) {
    if (typeof h != "number" || h < 0 || i(h))
      throw new RangeError('The value of "n" is out of range. It must be a non-negative number. Received ' + h + ".");
    return this._maxListeners = h, this;
  };
  function s(g) {
    return g._maxListeners === void 0 ? o.defaultMaxListeners : g._maxListeners;
  }
  o.prototype.getMaxListeners = function() {
    return s(this);
  }, o.prototype.emit = function(h) {
    for (var b = [], A = 1; A < arguments.length; A++) b.push(arguments[A]);
    var S = h === "error", $ = this._events;
    if ($ !== void 0)
      S = S && $.error === void 0;
    else if (!S)
      return !1;
    if (S) {
      var C;
      if (b.length > 0 && (C = b[0]), C instanceof Error)
        throw C;
      var R = new Error("Unhandled error." + (C ? " (" + C.message + ")" : ""));
      throw R.context = C, R;
    }
    var V = $[h];
    if (V === void 0)
      return !1;
    if (typeof V == "function")
      t(V, this, b);
    else
      for (var K = V.length, T = _(V, K), A = 0; A < K; ++A)
        t(T[A], this, b);
    return !0;
  };
  function c(g, h, b, A) {
    var S, $, C;
    if (u(b), $ = g._events, $ === void 0 ? ($ = g._events = /* @__PURE__ */ Object.create(null), g._eventsCount = 0) : ($.newListener !== void 0 && (g.emit(
      "newListener",
      h,
      b.listener ? b.listener : b
    ), $ = g._events), C = $[h]), C === void 0)
      C = $[h] = b, ++g._eventsCount;
    else if (typeof C == "function" ? C = $[h] = A ? [b, C] : [C, b] : A ? C.unshift(b) : C.push(b), S = s(g), S > 0 && C.length > S && !C.warned) {
      C.warned = !0;
      var R = new Error("Possible EventEmitter memory leak detected. " + C.length + " " + String(h) + " listeners added. Use emitter.setMaxListeners() to increase limit");
      R.name = "MaxListenersExceededWarning", R.emitter = g, R.type = h, R.count = C.length, n(R);
    }
    return g;
  }
  o.prototype.addListener = function(h, b) {
    return c(this, h, b, !1);
  }, o.prototype.on = o.prototype.addListener, o.prototype.prependListener = function(h, b) {
    return c(this, h, b, !0);
  };
  function p() {
    if (!this.fired)
      return this.target.removeListener(this.type, this.wrapFn), this.fired = !0, arguments.length === 0 ? this.listener.call(this.target) : this.listener.apply(this.target, arguments);
  }
  function f(g, h, b) {
    var A = { fired: !1, wrapFn: void 0, target: g, type: h, listener: b }, S = p.bind(A);
    return S.listener = b, A.wrapFn = S, S;
  }
  o.prototype.once = function(h, b) {
    return u(b), this.on(h, f(this, h, b)), this;
  }, o.prototype.prependOnceListener = function(h, b) {
    return u(b), this.prependListener(h, f(this, h, b)), this;
  }, o.prototype.removeListener = function(h, b) {
    var A, S, $, C, R;
    if (u(b), S = this._events, S === void 0)
      return this;
    if (A = S[h], A === void 0)
      return this;
    if (A === b || A.listener === b)
      --this._eventsCount === 0 ? this._events = /* @__PURE__ */ Object.create(null) : (delete S[h], S.removeListener && this.emit("removeListener", h, A.listener || b));
    else if (typeof A != "function") {
      for ($ = -1, C = A.length - 1; C >= 0; C--)
        if (A[C] === b || A[C].listener === b) {
          R = A[C].listener, $ = C;
          break;
        }
      if ($ < 0)
        return this;
      $ === 0 ? A.shift() : w(A, $), A.length === 1 && (S[h] = A[0]), S.removeListener !== void 0 && this.emit("removeListener", h, R || b);
    }
    return this;
  }, o.prototype.off = o.prototype.removeListener, o.prototype.removeAllListeners = function(h) {
    var b, A, S;
    if (A = this._events, A === void 0)
      return this;
    if (A.removeListener === void 0)
      return arguments.length === 0 ? (this._events = /* @__PURE__ */ Object.create(null), this._eventsCount = 0) : A[h] !== void 0 && (--this._eventsCount === 0 ? this._events = /* @__PURE__ */ Object.create(null) : delete A[h]), this;
    if (arguments.length === 0) {
      var $ = Object.keys(A), C;
      for (S = 0; S < $.length; ++S)
        C = $[S], C !== "removeListener" && this.removeAllListeners(C);
      return this.removeAllListeners("removeListener"), this._events = /* @__PURE__ */ Object.create(null), this._eventsCount = 0, this;
    }
    if (b = A[h], typeof b == "function")
      this.removeListener(h, b);
    else if (b !== void 0)
      for (S = b.length - 1; S >= 0; S--)
        this.removeListener(h, b[S]);
    return this;
  };
  function d(g, h, b) {
    var A = g._events;
    if (A === void 0)
      return [];
    var S = A[h];
    return S === void 0 ? [] : typeof S == "function" ? b ? [S.listener || S] : [S] : b ? O(S) : _(S, S.length);
  }
  o.prototype.listeners = function(h) {
    return d(this, h, !0);
  }, o.prototype.rawListeners = function(h) {
    return d(this, h, !1);
  }, o.listenerCount = function(g, h) {
    return typeof g.listenerCount == "function" ? g.listenerCount(h) : P.call(g, h);
  }, o.prototype.listenerCount = P;
  function P(g) {
    var h = this._events;
    if (h !== void 0) {
      var b = h[g];
      if (typeof b == "function")
        return 1;
      if (b !== void 0)
        return b.length;
    }
    return 0;
  }
  o.prototype.eventNames = function() {
    return this._eventsCount > 0 ? r(this._events) : [];
  };
  function _(g, h) {
    for (var b = new Array(h), A = 0; A < h; ++A)
      b[A] = g[A];
    return b;
  }
  function w(g, h) {
    for (; h + 1 < g.length; h++)
      g[h] = g[h + 1];
    g.pop();
  }
  function O(g) {
    for (var h = new Array(g.length), b = 0; b < h.length; ++b)
      h[b] = g[b].listener || g[b];
    return h;
  }
  function j(g, h) {
    return new Promise(function(b, A) {
      function S(C) {
        g.removeListener(h, $), A(C);
      }
      function $() {
        typeof g.removeListener == "function" && g.removeListener("error", S), b([].slice.call(arguments));
      }
      E(g, h, $, { once: !0 }), h !== "error" && I(g, S, { once: !0 });
    });
  }
  function I(g, h, b) {
    typeof g.on == "function" && E(g, "error", h, b);
  }
  function E(g, h, b, A) {
    if (typeof g.on == "function")
      A.once ? g.once(h, b) : g.on(h, b);
    else if (typeof g.addEventListener == "function")
      g.addEventListener(h, function S($) {
        A.once && g.removeEventListener(h, S), b($);
      });
    else
      throw new TypeError('The "emitter" argument must be of type EventEmitter. Received type ' + typeof g);
  }
  return Ie.exports;
}
var zt, wa;
function hf() {
  if (wa) return zt;
  wa = 1;
  const e = /* @__PURE__ */ di(), t = /* @__PURE__ */ Jc(), r = /* @__PURE__ */ zc(), n = /* @__PURE__ */ rf(), i = /* @__PURE__ */ uf(), o = /* @__PURE__ */ ff(), a = /* @__PURE__ */ df(), u = /* @__PURE__ */ yf(), s = vf(), { EventEmitter: c } = gf();
  function p(f) {
    return s.fromNullable(f[0]).value();
  }
  return zt = function({ dataclasses: f, invariants: d, errors: P, rules: _, UITypes: w }) {
    const { CompoundPredicate: O, ComparisonPredicate: j, Predicate: I } = f, E = (T, B) => s.fromNullable(t((U) => U.type_id === B, T)), g = (T, B) => d.Target_idMustReferToADefinedTarget(
      t((U) => U.target_id === B, T)
    ), h = (T, B) => d.LogicalType_idMustReferToADefinedLogicalType(
      t(
        (U) => U.logicalType_id === B,
        T
      )
    ), b = (T, B) => d.Operator_idMustReferToADefinedOperator(
      t((U) => U.operator_id === B, T)
    ), A = r(
      (T, B) => n(i(({ operator_id: U }) => B.includes(U)))(
        T
      )
    ), S = r((T, B) => (B.$operators = A(T.operators, B.operator_ids), B)), $ = r((T, B) => {
      const U = E(T.types, B.type_id);
      return d.TargetMustReferToADefinedType(U, B).then((X) => (B.$type = X, B));
    }), C = (T) => function(B) {
      return B.then((U) => (T(), U));
    }, R = (T, B) => n(T, C(B)), V = (T) => {
      T.operators = o(f.Operator, T.operators), T.logicalTypes = o(f.LogicalType, T.logicalTypes), T.argumentTypes = o(
        f.ArgumentType,
        T.argumentTypes || []
      );
      const B = n(f.Type, S(T));
      T.types = o(B, T.types);
      const U = n(f.Target, $(T));
      return Promise.all(o(U, T.targets)).then((X) => (T.targets = X, T));
    };
    function K(T) {
      const { data: B, columns: U, ui: X, options: re } = T, se = X || {};
      return new Promise((J, Y) => {
        try {
          f._requireProps(
            U,
            "operators,logicalTypes,types,targets"
          );
        } catch (te) {
          Y(te);
          return;
        }
        J();
      }).then(() => V(U)).then((J) => {
        let Y, te;
        const l = new c(), y = e(K.defaults.options, re);
        function m() {
          const F = !_.predicateToRemoveIsTheLastComparisonPredicate(
            Y,
            O,
            j
          );
          O.forEach(Y, (x) => {
            x.$canBeRemoved = F && !_.predicateToRemoveIsRootPredicate(Y, x);
          });
        }
        function D() {
          l.emit("changed", te);
        }
        const N = n(m, D);
        function G(F) {
          return d.RootPredicateMustBeACompoundPredicate(F, O).then(() => {
            Y = F;
          });
        }
        function M(F) {
          return I.fromJSON(F, {
            getTargetById: (x) => g(J.targets, x),
            getLogicalTypeById: (x) => h(J.logicalTypes, x),
            getOperatorById: (x) => b(J.operators, x)
          });
        }
        function q({ where: F, how: x = "after", type: ee }) {
          return Promise.resolve().then(() => d.AddOnlySupportsAfter(x)).then(
            () => d.PredicateTypeMustBeValid(ee, I.Types)
          ).then(() => y[`getDefault${ee}`](J, y)).then((me) => {
            const je = j.is(F);
            if (je || O.is(F)) {
              if (je) {
                const Is = ve(F), [bi, [Ip, Cs]] = a(2, Is);
                bi.predicates = u(
                  Cs + 1,
                  me,
                  bi.predicates
                );
              } else
                F.predicates.unshift(me);
              return me;
            }
            return Promise.reject(
              new P.CannotAddSomethingElseThanACompoundPredicateOrAComparisonPredicate()
            );
          });
        }
        function L(F) {
          return Promise.resolve().then(
            () => d.RemovePredicateMustDifferFromRootPredicate(
              Y,
              F
            )
          ).then(
            () => d.RemovePredicateCannotBeTheLastComparisonPredicate(
              Y,
              F,
              O,
              j
            )
          ).then(() => {
            if (O.is(F) || j.is(F)) {
              const x = ve(F), [ee, [me, je]] = a(2, x);
              return ee.predicates.splice(je, 1), ee.predicates.length === 0 ? L(ee) : F;
            }
            return Promise.reject(
              new P.CannotRemoveSomethingElseThanACompoundPredicateOrAComparisonPredicate()
            );
          });
        }
        function k(F, x) {
          return d.PredicateMustBeACompoundPredicate(F, O).then(() => h(
            J.logicalTypes,
            x
          )).then((ee) => {
            F.logic = ee;
          });
        }
        function z(F, x) {
          return d.PredicateMustBeAComparisonPredicate(
            F,
            j
          ).then(() => g(J.targets, x)).then((ee) => (F.target = ee, Z(
            F,
            p(F.target.$type.$operators).operator_id
          )));
        }
        function Z(F, x) {
          return Promise.resolve().then(
            () => d.Operator_idMustReferToADefinedOperator(
              F.target.$type.$operators.find(
                (ee) => ee.operator_id === x
              )
            )
          ).then((ee) => {
            F.operator = ee, F.argument = null;
          });
        }
        function ne(F, x) {
          return Promise.resolve().then(() => {
            F.argument = x;
          });
        }
        function H(F) {
          return s.fromNullable(
            J.argumentTypes.find(
              (x) => x.argumentType_id === F
            )
          ).map((x) => x.component).valueOrElse(
            () => y.getDefaultArgumentComponent(J, y, se)
          );
        }
        function ye(F) {
          return X[F];
        }
        function ve(F) {
          return O.reduce(
            Y,
            (x, ee, me) => F === ee ? me : x,
            null
          );
        }
        function v() {
          return I.toJSON(Y);
        }
        function pe(F, x) {
          l.on(F, x);
        }
        function ae(F, x) {
          l.once(F, x);
        }
        function he(F, x) {
          F ? x ? l.removeListener(F, x) : l.removeAllListeners(F) : l.removeAllListeners();
        }
        return (B ? M(B) : y.getDefaultData(J, y)).then(R(G, N)).then(() => (te = {
          on: pe,
          once: ae,
          off: he,
          setData: R(G, N),
          add: R(q, N),
          remove: R(L, N),
          setPredicateTarget_id: R(
            z,
            N
          ),
          setPredicateOperator_id: R(
            Z,
            N
          ),
          setPredicateLogicalType_id: R(
            k,
            N
          ),
          setArgumentValue: R(ne, N),
          getArgumentTypeComponentById: H,
          /**
           * Enumeration of overridable core ui-predicate component
           * @enum {String}
           */
          UITypes: w,
          /**
           * Get core UI component (e.g. target selector)
           * @param {core.ui} ui component name
           * @return {Object} component
           * @memberof core.api
           */
          getUIComponent: ye,
          toJSON: v,
          /**
           * Get root CompoundPredicate
           * @return {dataclasses.CompoundPredicate} root CompoundPredicate
           * @memberof core.api
           */
          get root() {
            return Y;
          },
          // used for testing
          get columns() {
            return J;
          },
          // used for testing
          get options() {
            return y;
          }
        }, te));
      });
    }
    return K.defaults = {
      /**
       * Defaults options of PredicateCore
       * @type {Object}
       * @namespace core.defaults.options
       */
      options: {
        /**
         * When data is not set at construction time PredicateCore default behavior will be to use the first target and its first operator with empty argument
         * @param  {Object} columns every necessary data class
         * @param  {Object} options PredicateCore available options
         * @return {Promise<dataclasses.CompoundPredicate>}  root CompoundPredicate
         * @since 1.0.0
         * @memberof core.defaults.options
         */
        getDefaultData(T, B) {
          return B.getDefaultComparisonPredicate(T, B).then((U) => B.getDefaultCompoundPredicate(T, B, [
            U
          ]));
        },
        /**
         * Default compount predicate to use
         *
         * This function is called whenever a new CompoundPredicate is added to the UIPredicate
         * @param  {Object} columns specified columns
         * @param  {Object} options PredicateCore available options
         * @param  {Array<dataclasses.Predicate>} predicates array of predicates to include into the CompoundPredicate
         * @return {Promise<dataclasses.CompoundPredicate>} a CompoundPredicate
         * @since 1.0.0
         * @memberof core.defaults.options
         */
        getDefaultCompoundPredicate(T, B, U) {
          return (!Array.isArray(U) || U.length === 0 ? B.getDefaultComparisonPredicate(T, B).then((X) => [X]) : Promise.resolve(U)).then(
            (X) => B.getDefaultLogicalType(X, T, B).then((re) => O(re, X))
          );
        },
        /**
         * Default comparison predicate to use
         *
         * This function is called whenever a new ComparisonPredicate is added to the UIPredicate
         * @param  {Object} columns specified columns
         * @param  {Object} [options=PredicateCore.defaults.options] PredicateCore available options
         * @return {Promise<dataclasses.ComparisonPredicate>} a Comparison
         * @since 1.0.0
         * @memberof core.defaults.options
         */
        getDefaultComparisonPredicate(T, B) {
          const U = p(T.targets);
          return j(
            U,
            p(U.$type.$operators)
          );
        },
        /**
         * Default logical type to use when a new comparison predicate is created
         *
         * This function is called whenever a new ComparisonPredicate is added to the UIPredicate
         * @param  {Array<dataclasses.Predicate>} predicates specified columns
         * @param  {Object} columns specified columns
         * @param  {Object} [options=PredicateCore.defaults.options] PredicateCore available options
         * @return {Promise<dataclasses.LogicalType>} a logical type
         * @since 1.0.0
         * @memberof core.defaults.options
         */
        getDefaultLogicalType(T, B, U) {
          return Promise.resolve(p(B.logicalTypes));
        },
        /**
         * Get the default UI component for any argument. Also used if no registered UI component match `argumentType_id`
         * @param  {Object} columns specified columns
         * @param  {Object} [options=PredicateCore.defaults.options] PredicateCore available options
         * @return {*} yield a UI Component (depending on the UI Framework used)
         * @throws if the UI Framework adapter did not override this function. Each UI Framework adapter (e.g. ui-predicate-vue, ui-predicate-react, ...) must implement this and let user override it
         * @memberof core.defaults.options
         */
        getDefaultArgumentComponent(T, B) {
          throw new P.UIFrameworkMustImplementgetDefaultArgumentComponent(
            "UIFrameworkMustImplementgetDefaultArgumentComponent"
          );
        }
      }
    }, K;
  }, zt;
}
var Ht, ja;
function mf() {
  if (ja) return Ht;
  ja = 1;
  const e = Mc(), t = Lc(), r = Dc(), n = Nc()({ errors: e, rules: r }), i = kc()({ invariants: n, errors: e });
  return Ht = { PredicateCore: hf()({
    dataclasses: i,
    invariants: n,
    errors: e,
    rules: r,
    UITypes: t
  }), errors: e, invariants: n, UITypes: t, dataclasses: i }, Ht;
}
var le = mf(), Kt, Ea;
function ls() {
  if (Ea) return Kt;
  Ea = 1;
  function e(t, r, n) {
    return t === t && (n !== void 0 && (t = t <= n ? t : n), r !== void 0 && (t = t >= r ? t : r)), t;
  }
  return Kt = e, Kt;
}
var Xt, Ia;
function _f() {
  if (Ia) return Xt;
  Ia = 1;
  function e(t, r) {
    var n = -1, i = t.length;
    for (r || (r = Array(i)); ++n < i; )
      r[n] = t[n];
    return r;
  }
  return Xt = e, Xt;
}
var Yt, Ca;
function bf() {
  if (Ca) return Yt;
  Ca = 1;
  var e = Math.floor, t = Math.random;
  function r(n, i) {
    return n + e(t() * (i - n + 1));
  }
  return Yt = r, Yt;
}
var Zt, Fa;
function ds() {
  if (Fa) return Zt;
  Fa = 1;
  var e = bf();
  function t(r, n) {
    var i = -1, o = r.length, a = o - 1;
    for (n = n === void 0 ? o : n; ++i < n; ) {
      var u = e(i, a), s = r[u];
      r[u] = r[i], r[i] = s;
    }
    return r.length = n, r;
  }
  return Zt = t, Zt;
}
var Qt, Ba;
function Af() {
  if (Ba) return Qt;
  Ba = 1;
  var e = ls(), t = _f(), r = ds();
  function n(i, o) {
    return r(t(i), e(o, 0, i.length));
  }
  return Qt = n, Qt;
}
var en, Ma;
function Pf() {
  if (Ma) return en;
  Ma = 1;
  function e(t, r) {
    for (var n = -1, i = t == null ? 0 : t.length, o = Array(i); ++n < i; )
      o[n] = r(t[n], n, t);
    return o;
  }
  return en = e, en;
}
var rn, La;
function Of() {
  if (La) return rn;
  La = 1;
  var e = Pf();
  function t(r, n) {
    return e(n, function(i) {
      return r[i];
    });
  }
  return rn = t, rn;
}
var tn, Da;
function Tf() {
  if (Da) return tn;
  Da = 1;
  function e(t, r) {
    for (var n = -1, i = Array(t); ++n < t; )
      i[n] = r(n);
    return i;
  }
  return tn = e, tn;
}
var nn, Na;
function ys() {
  if (Na) return nn;
  Na = 1;
  var e = typeof _e == "object" && _e && _e.Object === Object && _e;
  return nn = e, nn;
}
var on, $a;
function hi() {
  if ($a) return on;
  $a = 1;
  var e = ys(), t = typeof self == "object" && self && self.Object === Object && self, r = e || t || Function("return this")();
  return on = r, on;
}
var an, Ua;
function vs() {
  if (Ua) return an;
  Ua = 1;
  var e = hi(), t = e.Symbol;
  return an = t, an;
}
var un, ka;
function Sf() {
  if (ka) return un;
  ka = 1;
  var e = vs(), t = Object.prototype, r = t.hasOwnProperty, n = t.toString, i = e ? e.toStringTag : void 0;
  function o(a) {
    var u = r.call(a, i), s = a[i];
    try {
      a[i] = void 0;
      var c = !0;
    } catch {
    }
    var p = n.call(a);
    return c && (u ? a[i] = s : delete a[i]), p;
  }
  return un = o, un;
}
var sn, xa;
function Rf() {
  if (xa) return sn;
  xa = 1;
  var e = Object.prototype, t = e.toString;
  function r(n) {
    return t.call(n);
  }
  return sn = r, sn;
}
var cn, Ga;
function xe() {
  if (Ga) return cn;
  Ga = 1;
  var e = vs(), t = Sf(), r = Rf(), n = "[object Null]", i = "[object Undefined]", o = e ? e.toStringTag : void 0;
  function a(u) {
    return u == null ? u === void 0 ? i : n : o && o in Object(u) ? t(u) : r(u);
  }
  return cn = a, cn;
}
var fn, Va;
function Ge() {
  if (Va) return fn;
  Va = 1;
  function e(t) {
    return t != null && typeof t == "object";
  }
  return fn = e, fn;
}
var pn, Ja;
function qf() {
  if (Ja) return pn;
  Ja = 1;
  var e = xe(), t = Ge(), r = "[object Arguments]";
  function n(i) {
    return t(i) && e(i) == r;
  }
  return pn = n, pn;
}
var ln, Wa;
function wf() {
  if (Wa) return ln;
  Wa = 1;
  var e = qf(), t = Ge(), r = Object.prototype, n = r.hasOwnProperty, i = r.propertyIsEnumerable, o = e(/* @__PURE__ */ function() {
    return arguments;
  }()) ? e : function(a) {
    return t(a) && n.call(a, "callee") && !i.call(a, "callee");
  };
  return ln = o, ln;
}
var dn, za;
function gs() {
  if (za) return dn;
  za = 1;
  var e = Array.isArray;
  return dn = e, dn;
}
var Pe = { exports: {} }, yn, Ha;
function jf() {
  if (Ha) return yn;
  Ha = 1;
  function e() {
    return !1;
  }
  return yn = e, yn;
}
Pe.exports;
var Ka;
function Ef() {
  return Ka || (Ka = 1, function(e, t) {
    var r = hi(), n = jf(), i = t && !t.nodeType && t, o = i && !0 && e && !e.nodeType && e, a = o && o.exports === i, u = a ? r.Buffer : void 0, s = u ? u.isBuffer : void 0, c = s || n;
    e.exports = c;
  }(Pe, Pe.exports)), Pe.exports;
}
var vn, Xa;
function hs() {
  if (Xa) return vn;
  Xa = 1;
  var e = 9007199254740991, t = /^(?:0|[1-9]\d*)$/;
  function r(n, i) {
    var o = typeof n;
    return i = i ?? e, !!i && (o == "number" || o != "symbol" && t.test(n)) && n > -1 && n % 1 == 0 && n < i;
  }
  return vn = r, vn;
}
var gn, Ya;
function ms() {
  if (Ya) return gn;
  Ya = 1;
  var e = 9007199254740991;
  function t(r) {
    return typeof r == "number" && r > -1 && r % 1 == 0 && r <= e;
  }
  return gn = t, gn;
}
var hn, Za;
function If() {
  if (Za) return hn;
  Za = 1;
  var e = xe(), t = ms(), r = Ge(), n = "[object Arguments]", i = "[object Array]", o = "[object Boolean]", a = "[object Date]", u = "[object Error]", s = "[object Function]", c = "[object Map]", p = "[object Number]", f = "[object Object]", d = "[object RegExp]", P = "[object Set]", _ = "[object String]", w = "[object WeakMap]", O = "[object ArrayBuffer]", j = "[object DataView]", I = "[object Float32Array]", E = "[object Float64Array]", g = "[object Int8Array]", h = "[object Int16Array]", b = "[object Int32Array]", A = "[object Uint8Array]", S = "[object Uint8ClampedArray]", $ = "[object Uint16Array]", C = "[object Uint32Array]", R = {};
  R[I] = R[E] = R[g] = R[h] = R[b] = R[A] = R[S] = R[$] = R[C] = !0, R[n] = R[i] = R[O] = R[o] = R[j] = R[a] = R[u] = R[s] = R[c] = R[p] = R[f] = R[d] = R[P] = R[_] = R[w] = !1;
  function V(K) {
    return r(K) && t(K.length) && !!R[e(K)];
  }
  return hn = V, hn;
}
var mn, Qa;
function Cf() {
  if (Qa) return mn;
  Qa = 1;
  function e(t) {
    return function(r) {
      return t(r);
    };
  }
  return mn = e, mn;
}
var Oe = { exports: {} };
Oe.exports;
var eu;
function Ff() {
  return eu || (eu = 1, function(e, t) {
    var r = ys(), n = t && !t.nodeType && t, i = n && !0 && e && !e.nodeType && e, o = i && i.exports === n, a = o && r.process, u = function() {
      try {
        var s = i && i.require && i.require("util").types;
        return s || a && a.binding && a.binding("util");
      } catch {
      }
    }();
    e.exports = u;
  }(Oe, Oe.exports)), Oe.exports;
}
var _n, ru;
function Bf() {
  if (ru) return _n;
  ru = 1;
  var e = If(), t = Cf(), r = Ff(), n = r && r.isTypedArray, i = n ? t(n) : e;
  return _n = i, _n;
}
var bn, tu;
function Mf() {
  if (tu) return bn;
  tu = 1;
  var e = Tf(), t = wf(), r = gs(), n = Ef(), i = hs(), o = Bf(), a = Object.prototype, u = a.hasOwnProperty;
  function s(c, p) {
    var f = r(c), d = !f && t(c), P = !f && !d && n(c), _ = !f && !d && !P && o(c), w = f || d || P || _, O = w ? e(c.length, String) : [], j = O.length;
    for (var I in c)
      (p || u.call(c, I)) && !(w && // Safari 9 has enumerable `arguments.length` in strict mode.
      (I == "length" || // Node.js 0.10 has enumerable non-index properties on buffers.
      P && (I == "offset" || I == "parent") || // PhantomJS 2 has enumerable non-index properties on typed arrays.
      _ && (I == "buffer" || I == "byteLength" || I == "byteOffset") || // Skip index properties.
      i(I, j))) && O.push(I);
    return O;
  }
  return bn = s, bn;
}
var An, nu;
function _s() {
  if (nu) return An;
  nu = 1;
  var e = Object.prototype;
  function t(r) {
    var n = r && r.constructor, i = typeof n == "function" && n.prototype || e;
    return r === i;
  }
  return An = t, An;
}
var Pn, iu;
function Lf() {
  if (iu) return Pn;
  iu = 1;
  function e(t, r) {
    return function(n) {
      return t(r(n));
    };
  }
  return Pn = e, Pn;
}
var On, ou;
function Df() {
  if (ou) return On;
  ou = 1;
  var e = Lf(), t = e(Object.keys, Object);
  return On = t, On;
}
var Tn, au;
function Nf() {
  if (au) return Tn;
  au = 1;
  var e = _s(), t = Df(), r = Object.prototype, n = r.hasOwnProperty;
  function i(o) {
    if (!e(o))
      return t(o);
    var a = [];
    for (var u in Object(o))
      n.call(o, u) && u != "constructor" && a.push(u);
    return a;
  }
  return Tn = i, Tn;
}
var Sn, uu;
function Ve() {
  if (uu) return Sn;
  uu = 1;
  function e(t) {
    var r = typeof t;
    return t != null && (r == "object" || r == "function");
  }
  return Sn = e, Sn;
}
var Rn, su;
function bs() {
  if (su) return Rn;
  su = 1;
  var e = xe(), t = Ve(), r = "[object AsyncFunction]", n = "[object Function]", i = "[object GeneratorFunction]", o = "[object Proxy]";
  function a(u) {
    if (!t(u))
      return !1;
    var s = e(u);
    return s == n || s == i || s == r || s == o;
  }
  return Rn = a, Rn;
}
var qn, cu;
function mi() {
  if (cu) return qn;
  cu = 1;
  var e = bs(), t = ms();
  function r(n) {
    return n != null && t(n.length) && !e(n);
  }
  return qn = r, qn;
}
var wn, fu;
function As() {
  if (fu) return wn;
  fu = 1;
  var e = Mf(), t = Nf(), r = mi();
  function n(i) {
    return r(i) ? e(i) : t(i);
  }
  return wn = n, wn;
}
var jn, pu;
function $f() {
  if (pu) return jn;
  pu = 1;
  var e = Of(), t = As();
  function r(n) {
    return n == null ? [] : e(n, t(n));
  }
  return jn = r, jn;
}
var En, lu;
function Uf() {
  if (lu) return En;
  lu = 1;
  var e = ls(), t = ds(), r = $f();
  function n(i, o) {
    var a = r(i);
    return t(a, e(o, 0, a.length));
  }
  return En = n, En;
}
var In, du;
function Ps() {
  if (du) return In;
  du = 1;
  function e(t, r) {
    return t === r || t !== t && r !== r;
  }
  return In = e, In;
}
var Cn, yu;
function Os() {
  if (yu) return Cn;
  yu = 1;
  var e = Ps(), t = mi(), r = hs(), n = Ve();
  function i(o, a, u) {
    if (!n(u))
      return !1;
    var s = typeof a;
    return (s == "number" ? t(u) && r(a, u.length) : s == "string" && a in u) ? e(u[a], o) : !1;
  }
  return Cn = i, Cn;
}
var Fn, vu;
function kf() {
  if (vu) return Fn;
  vu = 1;
  var e = /\s/;
  function t(r) {
    for (var n = r.length; n-- && e.test(r.charAt(n)); )
      ;
    return n;
  }
  return Fn = t, Fn;
}
var Bn, gu;
function xf() {
  if (gu) return Bn;
  gu = 1;
  var e = kf(), t = /^\s+/;
  function r(n) {
    return n && n.slice(0, e(n) + 1).replace(t, "");
  }
  return Bn = r, Bn;
}
var Mn, hu;
function Gf() {
  if (hu) return Mn;
  hu = 1;
  var e = xe(), t = Ge(), r = "[object Symbol]";
  function n(i) {
    return typeof i == "symbol" || t(i) && e(i) == r;
  }
  return Mn = n, Mn;
}
var Ln, mu;
function Vf() {
  if (mu) return Ln;
  mu = 1;
  var e = xf(), t = Ve(), r = Gf(), n = NaN, i = /^[-+]0x[0-9a-f]+$/i, o = /^0b[01]+$/i, a = /^0o[0-7]+$/i, u = parseInt;
  function s(c) {
    if (typeof c == "number")
      return c;
    if (r(c))
      return n;
    if (t(c)) {
      var p = typeof c.valueOf == "function" ? c.valueOf() : c;
      c = t(p) ? p + "" : p;
    }
    if (typeof c != "string")
      return c === 0 ? c : +c;
    c = e(c);
    var f = o.test(c);
    return f || a.test(c) ? u(c.slice(2), f ? 2 : 8) : i.test(c) ? n : +c;
  }
  return Ln = s, Ln;
}
var Dn, _u;
function Jf() {
  if (_u) return Dn;
  _u = 1;
  var e = Vf(), t = 1 / 0, r = 17976931348623157e292;
  function n(i) {
    if (!i)
      return i === 0 ? i : 0;
    if (i = e(i), i === t || i === -1 / 0) {
      var o = i < 0 ? -1 : 1;
      return o * r;
    }
    return i === i ? i : 0;
  }
  return Dn = n, Dn;
}
var Nn, bu;
function Wf() {
  if (bu) return Nn;
  bu = 1;
  var e = Jf();
  function t(r) {
    var n = e(r), i = n % 1;
    return n === n ? i ? n - i : n : 0;
  }
  return Nn = t, Nn;
}
var $n, Au;
function zf() {
  if (Au) return $n;
  Au = 1;
  var e = Af(), t = Uf(), r = gs(), n = Os(), i = Wf();
  function o(a, u, s) {
    (s ? n(a, u, s) : u === void 0) ? u = 1 : u = i(u);
    var c = r(a) ? e : t;
    return c(a, u);
  }
  return $n = o, $n;
}
var Hf = zf();
const Kf = /* @__PURE__ */ ui(Hf);
var Un, Pu;
function Xf() {
  if (Pu) return Un;
  Pu = 1;
  var e = hi(), t = e["__core-js_shared__"];
  return Un = t, Un;
}
var kn, Ou;
function Yf() {
  if (Ou) return kn;
  Ou = 1;
  var e = Xf(), t = function() {
    var n = /[^.]+$/.exec(e && e.keys && e.keys.IE_PROTO || "");
    return n ? "Symbol(src)_1." + n : "";
  }();
  function r(n) {
    return !!t && t in n;
  }
  return kn = r, kn;
}
var xn, Tu;
function Zf() {
  if (Tu) return xn;
  Tu = 1;
  var e = Function.prototype, t = e.toString;
  function r(n) {
    if (n != null) {
      try {
        return t.call(n);
      } catch {
      }
      try {
        return n + "";
      } catch {
      }
    }
    return "";
  }
  return xn = r, xn;
}
var Gn, Su;
function Qf() {
  if (Su) return Gn;
  Su = 1;
  var e = bs(), t = Yf(), r = Ve(), n = Zf(), i = /[\\^$.*+?()[\]{}|]/g, o = /^\[object .+?Constructor\]$/, a = Function.prototype, u = Object.prototype, s = a.toString, c = u.hasOwnProperty, p = RegExp(
    "^" + s.call(c).replace(i, "\\$&").replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g, "$1.*?") + "$"
  );
  function f(d) {
    if (!r(d) || t(d))
      return !1;
    var P = e(d) ? p : o;
    return P.test(n(d));
  }
  return Gn = f, Gn;
}
var Vn, Ru;
function ep() {
  if (Ru) return Vn;
  Ru = 1;
  function e(t, r) {
    return t == null ? void 0 : t[r];
  }
  return Vn = e, Vn;
}
var Jn, qu;
function rp() {
  if (qu) return Jn;
  qu = 1;
  var e = Qf(), t = ep();
  function r(n, i) {
    var o = t(n, i);
    return e(o) ? o : void 0;
  }
  return Jn = r, Jn;
}
var Wn, wu;
function Ts() {
  if (wu) return Wn;
  wu = 1;
  var e = rp(), t = function() {
    try {
      var r = e(Object, "defineProperty");
      return r({}, "", {}), r;
    } catch {
    }
  }();
  return Wn = t, Wn;
}
var zn, ju;
function Ss() {
  if (ju) return zn;
  ju = 1;
  var e = Ts();
  function t(r, n, i) {
    n == "__proto__" && e ? e(r, n, {
      configurable: !0,
      enumerable: !0,
      value: i,
      writable: !0
    }) : r[n] = i;
  }
  return zn = t, zn;
}
var Hn, Eu;
function Rs() {
  if (Eu) return Hn;
  Eu = 1;
  var e = Ss(), t = Ps(), r = Object.prototype, n = r.hasOwnProperty;
  function i(o, a, u) {
    var s = o[a];
    (!(n.call(o, a) && t(s, u)) || u === void 0 && !(a in o)) && e(o, a, u);
  }
  return Hn = i, Hn;
}
var Kn, Iu;
function tp() {
  if (Iu) return Kn;
  Iu = 1;
  var e = Rs(), t = Ss();
  function r(n, i, o, a) {
    var u = !o;
    o || (o = {});
    for (var s = -1, c = i.length; ++s < c; ) {
      var p = i[s], f = a ? a(o[p], n[p], p, o, n) : void 0;
      f === void 0 && (f = n[p]), u ? t(o, p, f) : e(o, p, f);
    }
    return o;
  }
  return Kn = r, Kn;
}
var Xn, Cu;
function qs() {
  if (Cu) return Xn;
  Cu = 1;
  function e(t) {
    return t;
  }
  return Xn = e, Xn;
}
var Yn, Fu;
function np() {
  if (Fu) return Yn;
  Fu = 1;
  function e(t, r, n) {
    switch (n.length) {
      case 0:
        return t.call(r);
      case 1:
        return t.call(r, n[0]);
      case 2:
        return t.call(r, n[0], n[1]);
      case 3:
        return t.call(r, n[0], n[1], n[2]);
    }
    return t.apply(r, n);
  }
  return Yn = e, Yn;
}
var Zn, Bu;
function ip() {
  if (Bu) return Zn;
  Bu = 1;
  var e = np(), t = Math.max;
  function r(n, i, o) {
    return i = t(i === void 0 ? n.length - 1 : i, 0), function() {
      for (var a = arguments, u = -1, s = t(a.length - i, 0), c = Array(s); ++u < s; )
        c[u] = a[i + u];
      u = -1;
      for (var p = Array(i + 1); ++u < i; )
        p[u] = a[u];
      return p[i] = o(c), e(n, this, p);
    };
  }
  return Zn = r, Zn;
}
var Qn, Mu;
function op() {
  if (Mu) return Qn;
  Mu = 1;
  function e(t) {
    return function() {
      return t;
    };
  }
  return Qn = e, Qn;
}
var ei, Lu;
function ap() {
  if (Lu) return ei;
  Lu = 1;
  var e = op(), t = Ts(), r = qs(), n = t ? function(i, o) {
    return t(i, "toString", {
      configurable: !0,
      enumerable: !1,
      value: e(o),
      writable: !0
    });
  } : r;
  return ei = n, ei;
}
var ri, Du;
function up() {
  if (Du) return ri;
  Du = 1;
  var e = 800, t = 16, r = Date.now;
  function n(i) {
    var o = 0, a = 0;
    return function() {
      var u = r(), s = t - (u - a);
      if (a = u, s > 0) {
        if (++o >= e)
          return arguments[0];
      } else
        o = 0;
      return i.apply(void 0, arguments);
    };
  }
  return ri = n, ri;
}
var ti, Nu;
function sp() {
  if (Nu) return ti;
  Nu = 1;
  var e = ap(), t = up(), r = t(e);
  return ti = r, ti;
}
var ni, $u;
function cp() {
  if ($u) return ni;
  $u = 1;
  var e = qs(), t = ip(), r = sp();
  function n(i, o) {
    return r(t(i, o, e), i + "");
  }
  return ni = n, ni;
}
var ii, Uu;
function fp() {
  if (Uu) return ii;
  Uu = 1;
  var e = cp(), t = Os();
  function r(n) {
    return e(function(i, o) {
      var a = -1, u = o.length, s = u > 1 ? o[u - 1] : void 0, c = u > 2 ? o[2] : void 0;
      for (s = n.length > 3 && typeof s == "function" ? (u--, s) : void 0, c && t(o[0], o[1], c) && (s = u < 3 ? void 0 : s, u = 1), i = Object(i); ++a < u; ) {
        var p = o[a];
        p && n(i, p, a, s);
      }
      return i;
    });
  }
  return ii = r, ii;
}
var oi, ku;
function pp() {
  if (ku) return oi;
  ku = 1;
  var e = Rs(), t = tp(), r = fp(), n = mi(), i = _s(), o = As(), a = Object.prototype, u = a.hasOwnProperty, s = r(function(c, p) {
    if (i(p) || n(p)) {
      t(p, o(p), c);
      return;
    }
    for (var f in p)
      u.call(p, f) && e(c, f, p[f]);
  });
  return oi = s, oi;
}
var lp = pp();
const xu = /* @__PURE__ */ ui(lp), dp = ["value"], yp = ["value"], vp = {
  __name: "targets",
  props: {
    columns: {
      type: Object,
      required: !0
    },
    predicate: {
      type: Object,
      required: !0
    }
  },
  emits: ["change"],
  setup(e) {
    return (t, r) => (W(), Q("select", {
      value: e.predicate.target.target_id,
      onChange: r[0] || (r[0] = (n) => t.$emit("change", n.target.value))
    }, [
      (W(!0), Q(Se, null, Be(e.columns.targets, (n) => (W(), Q("option", {
        key: n.label,
        value: n.target_id
      }, Me(n.label), 9, yp))), 128))
    ], 40, dp));
  }
}, gp = ["value"], hp = ["value"], mp = {
  __name: "logical-types",
  props: {
    predicate: {
      type: Object,
      required: !0
    },
    columns: {
      type: Object,
      required: !0
    }
  },
  emits: ["change"],
  setup(e) {
    return (t, r) => (W(), Q("select", {
      value: e.predicate.logic.logicalType_id,
      onChange: r[0] || (r[0] = (n) => t.$emit("change", n.target.value))
    }, [
      (W(!0), Q(Se, null, Be(e.columns.logicalTypes, (n) => (W(), Q("option", {
        key: n.label,
        value: n.logicalType_id
      }, Me(n.label), 9, hp))), 128))
    ], 40, gp));
  }
}, _p = ["value"], bp = ["value"], Ap = {
  __name: "operators",
  props: {
    columns: {
      type: Object,
      required: !0
    },
    predicate: {
      type: Object,
      required: !0
    }
  },
  emits: ["change"],
  setup(e) {
    return (t, r) => (W(), Q("select", {
      value: e.predicate.operator.operator_id,
      onChange: r[0] || (r[0] = (n) => t.$emit("change", n.target.value))
    }, [
      (W(!0), Q(Se, null, Be(e.predicate.target.$type.$operators, (n) => (W(), Q("option", {
        key: n.label,
        value: n.operator_id
      }, Me(n.label), 9, bp))), 128))
    ], 40, _p));
  }
}, Pp = { type: "button" }, Op = {
  __name: "predicate-add",
  props: {
    isInAddCompoundMode: {
      type: Boolean,
      default: !1
    },
    predicate: {
      type: Object,
      required: !0
    }
  },
  setup(e) {
    return (t, r) => (W(), Q("button", Pp, Me(e.isInAddCompoundMode ? "…" : "+"), 1));
  }
}, Tp = ["disabled"], Sp = {
  __name: "predicate-remove",
  props: {
    disabled: {
      type: Boolean,
      default: !1
    },
    predicate: {
      type: Object
    }
  },
  setup(e) {
    return (t, r) => (W(), Q("button", {
      type: "button",
      disabled: e.disabled
    }, "-", 8, Tp));
  }
}, Rp = ["value"], qp = {
  __name: "argument-default",
  props: {
    value: {
      type: null,
      required: !0
    }
  },
  emits: ["change"],
  setup(e) {
    return (t, r) => (W(), Q("div", null, [
      fe("input", {
        type: "text",
        onChange: r[0] || (r[0] = (n) => t.$emit("change", n.target.value)),
        value: e.value
      }, null, 40, Rp)
    ]));
  }
}, wp = {
  [le.UITypes.TARGETS]: vp,
  [le.UITypes.LOGICAL_TYPES]: mp,
  [le.UITypes.OPERATORS]: Ap,
  [le.UITypes.PREDICATE_ADD]: Op,
  [le.UITypes.PREDICATE_REMOVE]: Sp,
  [le.UITypes.ARGUMENT_DEFAULT]: qp
}, ws = {
  options: {
    /**
     * UIPredicate Vue Adapter own default argument component
     * @param {Object} [columns=PredicateCore.defaults.columns] columns
     * @param {Object} [options=PredicateCore.defaults.options] options
     * @param {Object} [ui=PredicateCore.defaults.ui] ui
     * @return {Vue.component} the default Vue Component to use as argument specifier
     * @see core.defaults.getDefaultArgumentComponent
     * @memberof vue.defaults
     */
    getDefaultArgumentComponent(e, t, r) {
      return r[le.UITypes.ARGUMENT_DEFAULT];
    }
  }
};
function js({ data: e, columns: t, ui: r, options: n } = {}) {
  return le.PredicateCore({
    data: e,
    columns: t,
    ui: xu({}, wp, r),
    options: xu({}, ws.options, n)
  });
}
js.defaults = ws;
const jp = { class: "ui-predicate__main" }, _i = {
  __name: "ui-predicate",
  props: /* @__PURE__ */ Ai({
    columns: {
      type: Object,
      required: !0
    },
    ui: {
      type: Object,
      required: !1
    }
  }, {
    modelValue: { required: !0 },
    modelModifiers: {}
  }),
  emits: /* @__PURE__ */ Ai([
    "error",
    "changed",
    "initialized",
    "isInAddCompoundMode",
    "update:model-value"
  ], ["update:modelValue"]),
  setup(e, { emit: t }) {
    const r = Ms(e, "modelValue"), n = e, i = t, o = Je(!1), a = Je(!1), u = Je(null), s = Pi(null), c = Pi({});
    ce("UITypes", le.UITypes), ce("getAddCompoundMode", () => a.value), ce("add", (_) => s.value.add({
      // convert to plain object since ui-predicate-core doesn't handle js Proxies for now)
      // As Vue 3 has moved to using Proxy instead of Object.defineProperty for its reactivity system.
      // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Proxy
      where: Oi(_),
      how: "after",
      type: a.value ? "CompoundPredicate" : "ComparisonPredicate"
    })), ce("remove", (_) => s.value.remove(Oi(_))), ce(
      "setPredicateLogicalType_id",
      (_, w) => s.value.setPredicateLogicalType_id(_, w)
    ), ce(
      "setPredicateTarget_id",
      (_, w) => s.value.setPredicateTarget_id(_, w)
    ), ce(
      "setPredicateOperator_id",
      (_, w) => s.value.setPredicateOperator_id(_, w)
    ), ce(
      "getArgumentTypeComponentById",
      (_) => s.value.getArgumentTypeComponentById(_)
    ), ce("setArgumentValue", (_, w) => s.value.setArgumentValue(_, w)), ce("getUIComponent", (_) => s.value.getUIComponent(_));
    const p = (_) => {
      a.value = _, i("isInAddCompoundMode", _);
    }, f = (_) => {
      _.keyCode === 18 && p(!0);
    }, d = (_) => {
      _.keyCode === 18 && p(!1);
    }, P = () => {
      const _ = s.value.toJSON();
      i("changed", _), r.value = _, u.value = Kf("0123456789abcd", 8).join("");
    };
    return Ls(() => {
      window.addEventListener("keyup", d), window.addEventListener("keydown", f), js({
        data: r.value,
        columns: n.columns,
        ui: n.ui
      }).then(
        (_) => {
          s.value = _, c.value = _.root, s.value.on("changed", P), o.value = !0, i("initialized", s.value);
        },
        (_) => {
          const w = Object.assign(new es(), { cause: _ });
          return i("error", w), Promise.reject(w);
        }
      );
    }), Ds(() => {
      s.value && s.value.off(), window.removeEventListener("keyup", d), window.removeEventListener("keydown", f);
    }), (_, w) => {
      const O = be("ui-predicate-compound");
      return W(), Q("div", jp, [
        o.value ? (W(), ge(O, {
          key: u.value,
          predicate: c.value,
          columns: e.columns
        }, null, 8, ["predicate", "columns"])) : Ce("", !0)
      ]);
    };
  }
}, Ep = "1.0.1", Fe = {
  // https://w3c.github.io/webcomponents/spec/custom/#concepts
  // The custom element type identifies a custom element interface and is a sequence of characters that must match the NCName production,
  // must contain a U+002D HYPHEN-MINUS character, and must not contain any uppercase ASCII letters.
  "ui-predicate-options": rs,
  "ui-predicate-comparison": ts,
  "ui-predicate-comparison-argument": ns,
  "ui-predicate-compound": is,
  "ui-predicate": _i
}, Es = function(e) {
  Object.keys(Fe).forEach((t) => {
    e.component(t, Fe[t].default || Fe[t]);
  });
};
typeof window < "u" && window.Vue && (Es(window.Vue), window.UIPredicate = _i);
const Fp = {
  version: Ep,
  install: Es,
  components: Fe,
  UIPredicateOptions: rs,
  UIPredicateComparison: ts,
  UIPredicateComparisonArgument: ns,
  UIPredicateCompound: is,
  UIPredicate: _i,
  errors: es
};
export {
  Fe as components,
  Fp as default
};
